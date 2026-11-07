<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MrNewport\LaravelPlaid\DTOs\ExactTransaction;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\TransactionsService;
use MrNewport\LaravelPlaid\Support\ExactJsonNumbers;

beforeEach(function () {
    $this->wire = new MockHandler();
    $this->client = new PlaidClient([
        'client_id' => 'fictional-client', 'secret' => 'fictional-secret',
        'environment' => 'sandbox', 'version' => '2020-09-14',
        'urls' => ['sandbox' => 'https://sandbox.plaid.com'],
        'http_options' => ['handler' => HandlerStack::create($this->wire),
            'retry_enabled' => false],
        'logging' => ['enabled' => false],
    ]);
    $this->service = new TransactionsService($this->client);
});

it('keeps wire decimals, both currency alternatives, pending links and removed identifiers exact', function () {
    $body = <<<'JSON'
{"added":[{"transaction_id":"posted-A","account_id":"account-A","amount":-12345678901234567890.01,"iso_currency_code":"USD","unofficial_currency_code":null,"pending":false,"pending_transaction_id":"pending-A","date":"2030-11-05","name":"Quoted \"amount\": 41.09"},{"transaction_id":"coin-A","account_id":"account-B","amount":0.00000001,"iso_currency_code":null,"unofficial_currency_code":"BTC","pending":true,"date":"2030-11-05"}],"modified":[{"transaction_id":"changed-A","account_id":"account-A","amount":40.00,"iso_currency_code":"USD","unofficial_currency_code":null,"pending":false,"date":"2030-11-04"}],"removed":[{"transaction_id":"pending-A","account_id":"account-A"}],"next_cursor":"cursor-2","has_more":true,"request_id":"request-1"}
JSON;
    $this->wire->append(new Response(200, [], $body));

    $page = $this->service->syncExact('private-token', null, 2);

    expect($page['added'][0])->toBeInstanceOf(ExactTransaction::class)
        ->and($page['added'][0]->amount_decimal)->toBe('-12345678901234567890.01')
        ->and($page['added'][0]->iso_currency_code)->toBe('USD')
        ->and($page['added'][0]->pending)->toBeFalse()
        ->and($page['added'][0]->pending_transaction_id)->toBe('pending-A')
        ->and($page['added'][0]->name)->toBe('Quoted "amount": 41.09')
        ->and($page['added'][1]->amount_decimal)->toBe('0.00000001')
        ->and($page['added'][1]->unofficial_currency_code)->toBe('BTC')
        ->and($page['added'][1]->pending)->toBeTrue()
        ->and($page['modified'][0]->amount_decimal)->toBe('40.00')
        ->and($page['removed'])->toBe([['transaction_id' => 'pending-A', 'account_id' => 'account-A']])
        ->and($page['has_more'])->toBeTrue()
        ->and($page['next_cursor'])->toBe('cursor-2')
        ->and($page['response_sha256'])->toBe(hash('sha256', $body));
});

it('passes the saved cursor and original token to the next exact page', function () {
    $this->wire->append(new Response(200, [],
        '{"added":[],"modified":[],"removed":[],"next_cursor":"cursor-3","has_more":false,"request_id":"request-2"}'));

    $page = $this->service->syncExact('private-token', 'cursor-2', 50);

    expect($page['added'])->toBe([])->and($page['next_cursor'])->toBe('cursor-3');
    $request = $this->wire->getLastRequest();
    expect($request->getUri()->getPath())->toBe('/transactions/sync')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'access_token' => 'private-token', 'cursor' => 'cursor-2', 'count' => 50,
        ]);
});

it('uses a top-level account filter with an independent cursor per account', function () {
    $empty = '{"added":[],"modified":[],"removed":[],"next_cursor":"next","has_more":false,"request_id":"request"}';
    $this->wire->append(new Response(200, [], $empty), new Response(200, [], $empty),
        new Response(200, [], $empty));

    $this->service->syncExact('private-token', 'account-A-cursor', 20,
        ['include_original_description' => true], 'account-A');
    expect(json_decode((string) $this->wire->getLastRequest()->getBody(), true))->toBe([
        'access_token' => 'private-token', 'cursor' => 'account-A-cursor',
        'count' => 20, 'options' => ['include_original_description' => true],
        'account_id' => 'account-A',
    ]);

    $this->service->syncExact('private-token', 'account-B-cursor', 100, [], 'account-B');
    expect(json_decode((string) $this->wire->getLastRequest()->getBody(), true))->toBe([
        'access_token' => 'private-token', 'cursor' => 'account-B-cursor',
        'account_id' => 'account-B',
    ]);

    $this->service->syncExact('private-token', 'item-cursor');
    expect(json_decode((string) $this->wire->getLastRequest()->getBody(), true))->toBe([
        'access_token' => 'private-token', 'cursor' => 'item-cursor',
    ]);
});

it('rejects malformed account filters before sending a provider request', function () {
    foreach (['', ' account-A', "account-A\n", str_repeat('a', 256)] as $accountId) {
        expect(fn () => $this->service->syncExact('private-token', null, 100, [], $accountId))
            ->toThrow(InvalidArgumentException::class);
    }
    expect(fn () => $this->service->syncExact('private-token', null, 100,
        ['account_id' => 'account-A']))->toThrow(InvalidArgumentException::class);
    expect($this->wire->count())->toBe(0);
});

it('refuses a quoted, exponential, missing-currency or missing-pending amount', function ($replacement) {
    $body = '{"added":[{"transaction_id":"a","account_id":"b","amount":1.25,"iso_currency_code":"USD","unofficial_currency_code":null,"pending":false,"date":"2030-11-05"}],"modified":[],"removed":[],"next_cursor":"c","has_more":false,"request_id":"r"}';
    $this->wire->append(new Response(200, [], str_replace($replacement[0], $replacement[1], $body)));

    $this->service->syncExact('private-token');
})->with([
    'quoted amount' => [['"amount":1.25', '"amount":"1.25"']],
    'exponential amount' => [['"amount":1.25', '"amount":1.25e2']],
    'missing currency' => [['"iso_currency_code":"USD"', '"iso_currency_code":null']],
    'missing pending' => [['"pending":false', '"is_pending":false']],
])->throws(PlaidRequestException::class);

it('refuses malformed JSON and excessive response bytes without returning sensitive wire data', function () {
    $this->wire->append(new Response(200, [], '{"added": [1, } private-token'));
    try {
        $this->service->syncExact('private-token');
        test()->fail('Malformed wire response was accepted.');
    } catch (PlaidRequestException $exception) {
        expect($exception->getMessage())->not->toContain('private-token');
    }
    $this->wire->append(new Response(200, [], '123456'));
    expect(fn () => $this->client->postRaw('/transactions/sync', [], 4))
        ->toThrow(PlaidRequestException::class, 'supported bound');
});

it('refuses unbounded page requests and unsupported removed facts', function () {
    expect(fn () => $this->service->syncExact('private-token', null, 501))
        ->toThrow(InvalidArgumentException::class);
    $this->wire->append(new Response(200, [],
        '{"added":[],"modified":[],"removed":[{"transaction_id":"a"}],"next_cursor":"c","has_more":false,"request_id":"r"}'));
    expect(fn () => $this->service->syncExact('private-token'))
        ->toThrow(PlaidRequestException::class);
});

it('does not accept a provider object pretending to be an exact numeric token', function () {
    $this->wire->append(new Response(200, [],
        '{"added":[{"transaction_id":"a","account_id":"b","amount":{"__plaid_exact_json_number__":"1.25"},"iso_currency_code":"USD","unofficial_currency_code":null,"pending":false,"date":"2030-11-05"}],"modified":[],"removed":[],"next_cursor":"c","has_more":false,"request_id":"r"}'));

    expect(fn () => $this->service->syncExact('private-token'))
        ->toThrow(PlaidRequestException::class, 'unsupported exact amount');
});

it('keeps the existing relative endpoint and HTTP error guard on the raw path', function () {
    expect(fn () => $this->client->postRaw('https://other.example/transactions/sync'))
        ->toThrow(InvalidArgumentException::class);
    $this->wire->append(new Response(429, [],
        '{"error_message":"Rate limited","error_code":"RATE_LIMIT_EXCEEDED"}'));
    expect(fn () => $this->service->syncExact('private-token'))
        ->toThrow(\MrNewport\LaravelPlaid\Exceptions\PlaidRateLimitException::class);
});

it('leaves the established sync method and response shape available', function () {
    $this->wire->append(new Response(200, [],
        '{"added":[{"transaction_id":"a","account_id":"b","amount":1.25,"category":[],"date":"2030-11-05","is_pending":false}],"modified":[],"removed":[],"next_cursor":"c","has_more":false,"request_id":"r"}'));

    $legacy = $this->service->sync('private-token', null, 501);

    expect($legacy)->toHaveKeys(['added', 'modified', 'removed', 'next_cursor', 'has_more', 'request_id'])
        ->and($legacy['added'][0]->amount)->toBe(1.25)
        ->and($legacy['next_cursor'])->toBe('c');
});

it('stops millions of numeric tokens in a near-limit wire body before expansion', function () {
    $body = '{"numbers":['.str_repeat('0,', 3999999).'0]}';
    expect(strlen($body))->toBeGreaterThan(7900000)
        ->toBeLessThan(8388608);

    expect(fn () => ExactJsonNumbers::decode($body))
        ->toThrow(PlaidRequestException::class, 'too many numeric facts');
});

it('stops rewritten JSON growth even when raw bytes and token count are within limits', function () {
    $body = '{"padding":"'.str_repeat('x', 8000000).'","numbers":['
        .str_repeat('0,', 14999).'0]}';
    expect(strlen($body))->toBeLessThan(8388608);

    expect(fn () => ExactJsonNumbers::decode($body))
        ->toThrow(PlaidRequestException::class, 'expansion exceeds');
});

it('stops a large nonnumeric value array before native JSON decoding', function () {
    $body = '{"values":['.str_repeat('true,', 200000).'true]}';
    expect(fn () => ExactJsonNumbers::decode($body))
        ->toThrow(PlaidRequestException::class, 'too many JSON facts');
});

it('requests streaming only for the exact raw path and bounds oversized HTTP errors', function () {
    $seen = [];
    $handler = function ($request, array $options) use (&$seen) {
        $seen[] = $options;
        return \GuzzleHttp\Promise\Create::promiseFor(new Response(200, [],
            '{"added":[],"modified":[],"removed":[],"next_cursor":"c","has_more":false,"request_id":"r"}'));
    };
    $client = new PlaidClient([
        'client_id' => 'fictional-client', 'secret' => 'fictional-secret',
        'environment' => 'sandbox', 'version' => '2020-09-14',
        'urls' => ['sandbox' => 'https://sandbox.plaid.com'],
        'http_options' => ['handler' => HandlerStack::create($handler), 'retry_enabled' => false],
        'logging' => ['enabled' => false],
    ]);
    $client->postRaw('/transactions/sync');
    $client->post('/transactions/sync');
    expect($seen[0]['stream'])->toBeTrue()
        ->and(array_key_exists('stream', $seen[1]))->toBeFalse();

    $this->wire->append(new Response(400, [], str_repeat('private-token', 10000)));
    expect(fn () => $this->service->syncExact('private-token'))
        ->toThrow(PlaidRequestException::class, 'error response exceeds');
});
