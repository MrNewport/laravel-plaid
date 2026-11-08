<?php

namespace MrNewport\LaravelPlaid\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use Throwable;

/** Verifies Plaid's signed raw-body transport; the host owns Item and replay policy. */
final class WebhookVerificationService extends BaseService
{
    private const MAX_JWT_BYTES = 8192;
    private const MAX_BODY_BYTES = 65536;
    private const MAX_KEY_RESPONSE_BYTES = 16384;
    private const MAX_KEY_CACHE = 32;
    private const KEY_CACHE_SECONDS = 300;

    /** @var array<string,array{key:array,until:int}> */
    private array $keys = [];

    /** @var array<string,int> */
    private array $rejectedKids = [];

    private int $fetchWindowStart = 0;

    private int $fetchesInWindow = 0;

    /**
     * @return array{body:array,kid:string,issued_at:int,body_sha256:string,environment:string}
     */
    public function verify(string $verificationHeader, string $rawBody): array
    {
        $environment = $this->client->environment();
        $expectedBase = $environment === 'production'
            ? 'https://production.plaid.com' : 'https://sandbox.plaid.com';
        if (! in_array($environment, ['sandbox', 'production'], true)
            || rtrim($this->client->baseUrl(), '/') !== $expectedBase
            || $verificationHeader === '' || strlen($verificationHeader) > self::MAX_JWT_BYTES
            || $rawBody === '' || strlen($rawBody) > self::MAX_BODY_BYTES) {
            throw self::refused();
        }
        $segments = explode('.', $verificationHeader, 4);
        if (count($segments) !== 3 || strlen($segments[0]) > 1024
            || ! preg_match('/\A[A-Za-z0-9_-]+\z/D', $segments[0])) {
            throw self::refused();
        }
        try {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode($segments[0]));
        } catch (Throwable) {
            throw self::refused();
        }
        if (! is_object($header) || ($header->alg ?? null) !== 'ES256'
            || (isset($header->typ) && $header->typ !== 'JWT')
            || ! is_string($header->kid ?? null)
            || preg_match('/\A[A-Za-z0-9_-]{1,128}\z/D', $header->kid) !== 1) {
            throw self::refused();
        }
        $now = time();
        $jwk = $this->publicKey($environment, $header->kid, $now);
        try {
            $claims = JWT::decode($verificationHeader, JWK::parseKey($jwk));
        } catch (Throwable) {
            throw self::refused();
        }
        if (! is_int($claims->iat ?? null) || $claims->iat > $now + 30
            || $claims->iat < $now - 300
            || ! is_string($claims->request_body_sha256 ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/D', $claims->request_body_sha256) !== 1
            || ! hash_equals($claims->request_body_sha256, hash('sha256', $rawBody))) {
            throw self::refused();
        }
        try {
            $body = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw self::refused();
        }
        if (! is_array($body) || array_is_list($body)) {
            throw self::refused();
        }

        return ['body' => $body, 'kid' => $header->kid,
            'issued_at' => $claims->iat, 'body_sha256' => $claims->request_body_sha256,
            'environment' => $environment];
    }

    /** @return array<string,mixed> */
    private function publicKey(string $environment, string $kid, int $now): array
    {
        $cacheId = $environment.':'.$kid;
        if (isset($this->keys[$cacheId]) && $this->keys[$cacheId]['until'] > $now) {
            return $this->keys[$cacheId]['key'];
        }
        unset($this->keys[$cacheId]);
        if (($this->rejectedKids[$cacheId] ?? 0) > $now) {
            throw self::refused();
        }
        if ($this->fetchWindowStart <= $now - 60) {
            $this->fetchWindowStart = $now;
            $this->fetchesInWindow = 0;
        }
        if (++$this->fetchesInWindow > 8) {
            throw new PlaidRequestException('Plaid webhook verification is temporarily unavailable.');
        }
        try {
            $raw = $this->client->postRaw('/webhook_verification_key/get',
                ['key_id' => $kid], self::MAX_KEY_RESPONSE_BYTES);
            $response = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            if (! is_array($response) || array_is_list($response)) {
                throw self::refused();
            }
            $key = $response['key'] ?? null;
        } catch (Throwable) {
            $this->rejectKid($cacheId, $now);
            throw new PlaidRequestException('Plaid webhook verification is temporarily unavailable.');
        }
        if (! is_array($key) || ($key['alg'] ?? null) !== 'ES256'
            || ($key['kty'] ?? null) !== 'EC' || ($key['crv'] ?? null) !== 'P-256'
            || ($key['use'] ?? null) !== 'sig' || ($key['kid'] ?? null) !== $kid
            || isset($key['d']) || ! is_int($key['created_at'] ?? null)
            || $key['created_at'] > $now + 30
            || (! is_null($key['expired_at'] ?? null)
                && (! is_int($key['expired_at']) || $key['expired_at'] <= $now))
            || ! $this->coordinate($key['x'] ?? null)
            || ! $this->coordinate($key['y'] ?? null)) {
            $this->rejectKid($cacheId, $now);
            throw self::refused();
        }
        $until = min($now + self::KEY_CACHE_SECONDS,
            $key['expired_at'] ?? ($now + self::KEY_CACHE_SECONDS));
        if (count($this->keys) >= self::MAX_KEY_CACHE) {
            array_shift($this->keys);
        }
        $this->keys[$cacheId] = ['key' => $key, 'until' => $until];

        return $key;
    }

    private function rejectKid(string $cacheId, int $now): void
    {
        if (count($this->rejectedKids) >= self::MAX_KEY_CACHE) {
            array_shift($this->rejectedKids);
        }
        $this->rejectedKids[$cacheId] = $now + 30;
    }

    private function coordinate(mixed $value): bool
    {
        if (! is_string($value) || preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $value) !== 1) {
            return false;
        }
        try {
            return strlen(JWT::urlsafeB64Decode($value)) === 32;
        } catch (Throwable) {
            return false;
        }
    }

    private static function refused(): PlaidRequestException
    {
        return new PlaidRequestException('Plaid webhook verification failed.');
    }
}
