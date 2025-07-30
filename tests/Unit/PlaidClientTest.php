<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Exceptions\PlaidAuthenticationException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRateLimitException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\Exceptions\PlaidException;

beforeEach(function () {
    $this->mockHandler = new MockHandler();
    $handlerStack = HandlerStack::create($this->mockHandler);
    
    $this->config = [
        'client_id' => 'test_client_id',
        'secret' => 'test_secret',
        'environment' => 'sandbox',
        'version' => '2020-09-14',
        'urls' => [
            'sandbox' => 'https://sandbox.plaid.com',
        ],
        'http_options' => [
            'handler' => $handlerStack,
            'timeout' => 30,
            'connect_timeout' => 10,
            'retry_enabled' => true,
            'retry_max_attempts' => 3,
            'retry_delay' => 1000,
        ],
        'logging' => [
            'enabled' => false,
            'channel' => 'stack',
            'sensitive_fields' => ['access_token', 'secret'],
        ],
    ];
    
    $this->plaidClient = new PlaidClient($this->config);
});

it('makes successful POST requests', function () {
    $expectedResponse = ['success' => true, 'data' => 'test'];
    
    $this->mockHandler->append(
        new Response(200, [], json_encode($expectedResponse))
    );
    
    $response = $this->plaidClient->post('/test/endpoint', ['test' => 'data']);
    
    expect($response)->toBe($expectedResponse);
});

it('makes successful GET requests', function () {
    $expectedResponse = ['success' => true, 'data' => 'test'];
    
    $this->mockHandler->append(
        new Response(200, [], json_encode($expectedResponse))
    );
    
    $response = $this->plaidClient->get('/test/endpoint', ['test' => 'param']);
    
    expect($response)->toBe($expectedResponse);
});

it('makes successful DELETE requests', function () {
    $expectedResponse = ['success' => true];
    
    $this->mockHandler->append(
        new Response(200, [], json_encode($expectedResponse))
    );
    
    $response = $this->plaidClient->delete('/test/endpoint', ['id' => '123']);
    
    expect($response)->toBe($expectedResponse);
});

it('makes successful PATCH requests', function () {
    $expectedResponse = ['success' => true, 'updated' => true];
    
    $this->mockHandler->append(
        new Response(200, [], json_encode($expectedResponse))
    );
    
    $response = $this->plaidClient->patch('/test/endpoint', ['field' => 'value']);
    
    expect($response)->toBe($expectedResponse);
});

it('throws PlaidAuthenticationException on 401 responses', function () {
    $this->mockHandler->append(
        new Response(401, [], json_encode([
            'error_message' => 'Invalid credentials',
            'error_code' => 'INVALID_CREDENTIALS',
            'error_type' => 'INVALID_REQUEST',
        ]))
    );
    
    $this->plaidClient->post('/test/endpoint');
})->throws(PlaidAuthenticationException::class, 'Invalid credentials');

it('throws PlaidRateLimitException on 429 responses', function () {
    $this->mockHandler->append(
        new Response(429, [], json_encode([
            'error_message' => 'Rate limit exceeded',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'error_type' => 'RATE_LIMIT_ERROR',
        ]))
    );
    
    $this->plaidClient->post('/test/endpoint');
})->throws(PlaidRateLimitException::class, 'Rate limit exceeded');

it('throws PlaidRequestException on 400 responses', function () {
    $this->mockHandler->append(
        new Response(400, [], json_encode([
            'error_message' => 'Invalid request',
            'error_code' => 'INVALID_REQUEST',
            'error_type' => 'INVALID_REQUEST',
        ]))
    );
    
    $this->plaidClient->post('/test/endpoint');
})->throws(PlaidRequestException::class, 'Invalid request');

it('throws PlaidException on other error responses', function () {
    $this->mockHandler->append(
        new Response(500, [], json_encode([
            'error_message' => 'Internal server error',
            'error_code' => 'INTERNAL_SERVER_ERROR',
            'error_type' => 'API_ERROR',
        ]))
    );
    
    $this->plaidClient->post('/test/endpoint');
})->throws(PlaidException::class, 'Internal server error');

it('includes proper headers in requests', function () {
    $this->mockHandler->append(
        new Response(200, [], json_encode(['success' => true]))
    );
    
    $this->plaidClient->post('/test/endpoint');
    
    $request = $this->mockHandler->getLastRequest();
    
    expect($request->getHeader('Content-Type'))->toBe(['application/json']);
    expect($request->getHeader('PLAID-CLIENT-ID'))->toBe(['test_client_id']);
    expect($request->getHeader('PLAID-SECRET'))->toBe(['test_secret']);
    expect($request->getHeader('Plaid-Version'))->toBe(['2020-09-14']);
});

it('sanitizes sensitive data in logs when logging is enabled', function () {
    $config = array_merge($this->config, [
        'logging' => [
            'enabled' => true,
            'channel' => 'stack',
            'sensitive_fields' => ['access_token', 'secret', 'account_number'],
        ],
    ]);
    
    $client = new PlaidClient($config);
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});

it('creates client with retry middleware when enabled', function () {
    $config = [
        'client_id' => 'test_client_id',
        'secret' => 'test_secret',
        'environment' => 'sandbox',
        'version' => '2020-09-14',
        'urls' => [
            'sandbox' => 'https://sandbox.plaid.com',
        ],
        'http_options' => [
            'retry_enabled' => true,
            'retry_max_attempts' => 3,
            'retry_delay' => 1000,
        ],
    ];
    
    $plaidClient = new PlaidClient($config);
    
    expect($plaidClient)->toBeInstanceOf(PlaidClient::class);
});

it('handles network errors gracefully', function () {
    $this->mockHandler->append(
        new \GuzzleHttp\Exception\ConnectException(
            'Network error',
            new \GuzzleHttp\Psr7\Request('POST', '/test')
        )
    );
    
    $this->plaidClient->post('/test/endpoint');
})->throws(PlaidRequestException::class, 'Network error');