<?php

namespace MrNewport\LaravelPlaid;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Log;
use MrNewport\LaravelPlaid\Exceptions\PlaidException;
use MrNewport\LaravelPlaid\Exceptions\PlaidAuthenticationException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRateLimitException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PlaidClient
{
    protected Client $httpClient;
    protected array $config;
    protected string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['urls'][$config['environment']] ?? $config['urls']['sandbox'];
        
        $this->httpClient = $this->createHttpClient();
    }

    protected function createHttpClient(): Client
    {
        // Use provided handler if available (for testing)
        $stack = isset($this->config['http_options']['handler']) 
            ? $this->config['http_options']['handler'] 
            : HandlerStack::create();
        
        // Only add middleware if we created the stack ourselves
        if (!isset($this->config['http_options']['handler'])) {
            if ($this->config['logging']['enabled'] ?? false) {
                $stack->push($this->loggingMiddleware());
            }
            
            if ($this->config['http_options']['retry_enabled'] ?? true) {
                $stack->push($this->retryMiddleware());
            }
        }
        
        return new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->config['http_options']['timeout'] ?? 30,
            'connect_timeout' => $this->config['http_options']['connect_timeout'] ?? 10,
            'handler' => $stack,
            'headers' => [
                'Content-Type' => 'application/json',
                'PLAID-CLIENT-ID' => $this->config['client_id'],
                'PLAID-SECRET' => $this->config['secret'],
                'Plaid-Version' => $this->config['version'],
            ],
        ]);
    }

    public function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $options = [];
            
            if (!empty($data)) {
                if ($method === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }
            
            $response = $this->httpClient->request($method, $endpoint, $options);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $this->handleException($e);
        }
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    protected function handleException(GuzzleException $exception): void
    {
        if (!method_exists($exception, 'hasResponse') || !$exception->hasResponse()) {
            throw new PlaidRequestException('Network error: ' . $exception->getMessage(), 0, $exception);
        }
        
        $response = $exception->getResponse();
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);
        
        $errorMessage = $body['error_message'] ?? 'Unknown error';
        $errorCode = $body['error_code'] ?? 'UNKNOWN_ERROR';
        $errorType = $body['error_type'] ?? 'API_ERROR';
        
        switch ($statusCode) {
            case 400:
                throw new PlaidRequestException($errorMessage, $statusCode, $exception, $errorCode, $errorType);
            case 401:
                throw new PlaidAuthenticationException($errorMessage, $statusCode, $exception, $errorCode, $errorType);
            case 429:
                throw new PlaidRateLimitException($errorMessage, $statusCode, $exception, $errorCode, $errorType);
            default:
                throw new PlaidException($errorMessage, $statusCode, $exception, $errorCode, $errorType);
        }
    }

    protected function loggingMiddleware(): callable
    {
        return Middleware::tap(
            function (RequestInterface $request) {
                $body = $request->getBody()->getContents();
                $request->getBody()->rewind();
                
                Log::channel($this->config['logging']['channel'] ?? 'stack')->debug('Plaid API Request', [
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'headers' => $this->sanitizeHeaders($request->getHeaders()),
                    'body' => $this->sanitizeBody(json_decode($body, true)),
                ]);
            },
            function (RequestInterface $request, array $options, ResponseInterface $response) {
                $body = $response->getBody()->getContents();
                $response->getBody()->rewind();
                
                Log::channel($this->config['logging']['channel'] ?? 'stack')->debug('Plaid API Response', [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => $this->sanitizeBody(json_decode($body, true)),
                ]);
            }
        );
    }

    protected function retryMiddleware(): callable
    {
        return Middleware::retry(
            function ($retries, RequestInterface $request, ResponseInterface $response = null, \Exception $exception = null) {
                if ($retries >= ($this->config['http_options']['retry_max_attempts'] ?? 3)) {
                    return false;
                }
                
                if ($response && in_array($response->getStatusCode(), [429, 500, 502, 503, 504])) {
                    return true;
                }
                
                return false;
            },
            function ($retries) {
                return ($this->config['http_options']['retry_delay'] ?? 1000) * pow(2, $retries);
            }
        );
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['PLAID-SECRET', 'Authorization'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }
        
        return $headers;
    }

    protected function sanitizeBody(?array $body): ?array
    {
        if (!$body) {
            return null;
        }
        
        $sensitiveFields = $this->config['logging']['sensitive_fields'] ?? [];
        
        array_walk_recursive($body, function (&$value, $key) use ($sensitiveFields) {
            if (in_array($key, $sensitiveFields)) {
                $value = '***REDACTED***';
            }
        });
        
        return $body;
    }
}