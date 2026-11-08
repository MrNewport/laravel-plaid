<?php

use Firebase\JWT\JWT;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\WebhookVerificationService;

function webhookKeyPair(string $kid): array
{
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($key === false || ! openssl_pkey_export($key, $private)) {
        throw new RuntimeException('The test EC key is unavailable.');
    }
    $details = openssl_pkey_get_details($key);
    $url = static fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    return [$private, ['alg' => 'ES256', 'kty' => 'EC', 'crv' => 'P-256', 'use' => 'sig',
        'kid' => $kid, 'x' => $url($details['ec']['x']), 'y' => $url($details['ec']['y']),
        'created_at' => time() - 30, 'expired_at' => null]];
}

function webhookService(MockHandler $wire, string $environment = 'sandbox'): WebhookVerificationService
{
    $client = new PlaidClient(['client_id' => 'fictional-client', 'secret' => 'fictional-secret',
        'environment' => $environment, 'version' => '2020-09-14',
        'urls' => ['sandbox' => 'https://sandbox.plaid.com',
            'production' => 'https://production.plaid.com'],
        'http_options' => ['handler' => HandlerStack::create($wire), 'retry_enabled' => false],
        'logging' => ['enabled' => false]]);

    return new WebhookVerificationService($client);
}

function webhookJwt(string $private, string $kid, string $body, ?int $iat = null, ?string $hash = null): string
{
    return JWT::encode(['iat' => $iat ?? time(),
        'request_body_sha256' => $hash ?? hash('sha256', $body)], $private, 'ES256', $kid);
}

it('verifies a real P-256 signature, exact raw body and fixed key endpoint, then caches that key', function () {
    [$private, $jwk] = webhookKeyPair('key-one');
    $body = "{\"item_id\":\"item-A\",\n\"webhook_code\":\"SYNC_UPDATES_AVAILABLE\"}";
    $wire = new MockHandler([new Response(200, [], json_encode(['key' => $jwk]))]);
    $service = webhookService($wire);
    $jwt = webhookJwt($private, 'key-one', $body);

    $verified = $service->verify($jwt, $body);
    expect($verified['body'])->toBe(['item_id' => 'item-A', 'webhook_code' => 'SYNC_UPDATES_AVAILABLE'])
        ->and($verified['kid'])->toBe('key-one')
        ->and($verified['body_sha256'])->toBe(hash('sha256', $body))
        ->and($verified['environment'])->toBe('sandbox');
    $request = $wire->getLastRequest();
    expect($request->getUri()->getHost())->toBe('sandbox.plaid.com')
        ->and($request->getUri()->getPath())->toBe('/webhook_verification_key/get')
        ->and(json_decode((string) $request->getBody(), true))->toBe(['key_id' => 'key-one']);
    expect($service->verify($jwt, $body)['body'])->toBe($verified['body']);
});

it('refuses changed body whitespace, a different signature and a different signed body hash', function () {
    [$private, $jwk] = webhookKeyPair('key-one');
    [$otherPrivate] = webhookKeyPair('key-other');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler([new Response(200, [], json_encode(['key' => $jwk]))]);
    $service = webhookService($wire);
    $valid = webhookJwt($private, 'key-one', $body);
    expect($service->verify($valid, $body)['body']['item_id'])->toBe('item-A');
    expect(fn () => $service->verify($valid, "{ \"item_id\":\"item-A\"}"))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect(fn () => $service->verify(webhookJwt($otherPrivate, 'key-one', $body), $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect(fn () => $service->verify(webhookJwt($private, 'key-one', $body, null, str_repeat('0', 64)), $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
});

it('rejects stale, future and malformed issued-at claims after signature validation', function () {
    [$private, $jwk] = webhookKeyPair('key-time');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler([new Response(200, [], json_encode(['key' => $jwk]))]);
    $service = webhookService($wire);
    foreach ([time() - 301, time() + 31] as $iat) {
        expect(fn () => $service->verify(webhookJwt($private, 'key-time', $body, $iat), $body))
            ->toThrow(PlaidRequestException::class, 'verification failed');
    }
    $malformed = JWT::encode(['request_body_sha256' => hash('sha256', $body)],
        $private, 'ES256', 'key-time');
    expect(fn () => $service->verify($malformed, $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
});

it('rejects hostile headers and excessive bodies before any provider key request', function () {
    $wire = new MockHandler();
    $service = webhookService($wire);
    foreach (['', str_repeat('x', 8193), 'a.b', '!!!.bbb.ccc',
        JWT::encode(['iat' => time()], 'fictional-hmac-secret-at-least-32-bytes', 'HS256', 'kid')] as $header) {
        expect(fn () => $service->verify($header, '{}'))
            ->toThrow(PlaidRequestException::class, 'verification failed');
    }
    expect(fn () => $service->verify('a.b.c', str_repeat('x', 65537)))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect($wire->count())->toBe(0);
});

it('rejects wrong-key IDs, curves, expiration and a historical environment', function () {
    [$private, $jwk] = webhookKeyPair('key-good');
    $body = '{"item_id":"item-A"}';
    foreach ([
        array_replace($jwk, ['kid' => 'key-other']),
        array_replace($jwk, ['crv' => 'P-384']),
        array_replace($jwk, ['expired_at' => time() - 1]),
        array_replace($jwk, ['x' => 'short']),
        array_replace($jwk, ['use' => 'enc']),
        array_replace($jwk, ['d' => 'private']),
    ] as $badKey) {
        $service = webhookService(new MockHandler([new Response(200, [], json_encode(['key' => $badKey]))]));
        expect(fn () => $service->verify(webhookJwt($private, 'key-good', $body), $body))
            ->toThrow(PlaidRequestException::class, 'verification failed');
    }
    $wire = new MockHandler();
    expect(fn () => webhookService($wire, 'development')->verify(webhookJwt($private, 'key-good', $body), $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect($wire->count())->toBe(0);
});

it('rejects a non-JWT type, nonobject body and a mismatched configured base URL', function () {
    [$private, $jwk] = webhookKeyPair('key-type');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler([new Response(200, [], json_encode(['key' => $jwk]))]);
    $service = webhookService($wire);
    $wrongType = JWT::encode(['iat' => time(), 'request_body_sha256' => hash('sha256', $body)],
        $private, 'ES256', 'key-type', ['typ' => 'NOTJWT']);
    expect(fn () => $service->verify($wrongType, $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect($wire->count())->toBe(1);
    $bodyList = '[]';
    expect(fn () => $service->verify(webhookJwt($private, 'key-type', $bodyList), $bodyList))
        ->toThrow(PlaidRequestException::class, 'verification failed');

    $badClient = new PlaidClient(['client_id' => 'fictional', 'secret' => 'fictional',
        'environment' => 'production', 'version' => '2020-09-14',
        'urls' => ['sandbox' => 'https://sandbox.plaid.com'],
        'http_options' => ['handler' => HandlerStack::create($wire), 'retry_enabled' => false],
        'logging' => ['enabled' => false]]);
    expect(fn () => (new WebhookVerificationService($badClient))->verify(webhookJwt($private, 'key-type', $body), $body))
        ->toThrow(PlaidRequestException::class, 'verification failed');
    expect($wire->count())->toBe(0);
});

it('bounds distinct public-key fetches before a hostile kid stream can fan out', function () {
    [$private, $template] = webhookKeyPair('key-0');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler(array_map(fn (int $n) => new Response(200, [],
        json_encode(['key' => array_replace($template, ['kid' => 'key-'.$n])])), range(0, 7)));
    $service = webhookService($wire);
    foreach (range(0, 7) as $n) {
        expect($service->verify(webhookJwt($private, 'key-'.$n, $body), $body)['kid'])->toBe('key-'.$n);
    }
    expect(fn () => $service->verify(webhookJwt($private, 'key-8', $body), $body))
        ->toThrow(PlaidRequestException::class, 'temporarily unavailable');
    expect($wire->count())->toBe(0);
});

it('hides a provider key-fetch failure and accepts a new signed key by exact ID', function () {
    [$firstPrivate, $firstJwk] = webhookKeyPair('key-first');
    [$secondPrivate, $secondJwk] = webhookKeyPair('key-second');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler([
        new Response(200, [], json_encode(['key' => $firstJwk])),
        new Response(200, [], json_encode(['key' => $secondJwk])),
        new Response(500, [], '{"error_message":"private-provider-data"}'),
    ]);
    $service = webhookService($wire);
    expect($service->verify(webhookJwt($firstPrivate, 'key-first', $body), $body)['kid'])->toBe('key-first')
        ->and($service->verify(webhookJwt($secondPrivate, 'key-second', $body), $body)['kid'])->toBe('key-second');
    try {
        $service->verify(webhookJwt($firstPrivate, 'key-third', $body), $body);
        test()->fail('Provider failure was accepted.');
    } catch (PlaidRequestException $error) {
        expect($error->getMessage())->toBe('Plaid webhook verification is temporarily unavailable.')
            ->not->toContain('private-provider-data');
    }
});

it('bounds and sanitizes oversized or deeply nested provider key responses before JWK parsing', function () {
    [$private] = webhookKeyPair('key-bounded');
    $body = '{"item_id":"item-A"}';
    $wire = new MockHandler([
        new Response(200, [], str_repeat(' ', 16385)),
        new Response(200, [], '{"key":'.str_repeat('[', 20).'0'.str_repeat(']', 20).'}'),
    ]);
    $service = webhookService($wire);
    foreach (['key-bounded', 'key-nested'] as $kid) {
        try {
            $service->verify(webhookJwt($private, $kid, $body), $body);
            test()->fail('Unbounded provider response was accepted.');
        } catch (PlaidRequestException $error) {
            expect($error->getMessage())->toBe('Plaid webhook verification is temporarily unavailable.')
                ->not->toContain('item-A');
        }
    }
    expect($wire->count())->toBe(0);
});
