<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MrNewport\LaravelPlaid\DTOs\ExactTransaction;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\TransactionsService;

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
