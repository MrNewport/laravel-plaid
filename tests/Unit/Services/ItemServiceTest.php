<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\ItemService;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->itemService = new ItemService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('gets item information', function () {
    $mockResponse = [
        'item' => [
            'item_id' => 'item_123',
            'institution_id' => 'ins_123',
            'webhook' => 'https://example.com/webhook',
            'error' => null,
            'available_products' => ['balance', 'auth', 'transactions'],
            'billed_products' => ['transactions'],
        ],
        'status' => ['transactions' => ['last_successful_update' => '2024-01-15']],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/get', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->get('test_token');
    
    expect($result)->toHaveKeys(['item', 'status', 'request_id']);
    expect($result['item']['item_id'])->toBe('item_123');
});

it('removes an item', function () {
    $mockResponse = ['request_id' => 'test_request'];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/remove', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->remove('test_token');
    
    expect($result)->toHaveKey('request_id');
});

it('exchanges public token for access token', function () {
    $mockResponse = [
        'access_token' => 'access-sandbox-123',
        'item_id' => 'item_123',
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/public_token/exchange', ['public_token' => 'public-sandbox-123'])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->publicTokenExchange('public-sandbox-123');
    
    expect($result)->toHaveKeys(['access_token', 'item_id', 'request_id']);
    expect($result['access_token'])->toBe('access-sandbox-123');
});

it('creates public token from access token', function () {
    $mockResponse = [
        'public_token' => 'public-sandbox-123',
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/public_token/create', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->createPublicToken('test_token');
    
    expect($result)->toHaveKeys(['public_token', 'request_id']);
    expect($result['public_token'])->toBe('public-sandbox-123');
});

it('invalidates access token', function () {
    $mockResponse = [
        'new_access_token' => 'new-access-token',
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/access_token/invalidate', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->accessTokenInvalidate('test_token');
    
    expect($result)->toHaveKey('request_id');
});

it('updates webhook URL', function () {
    $webhookUrl = 'https://new-webhook.com/plaid';
    $mockResponse = [
        'item' => ['webhook' => $webhookUrl],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/webhook/update', [
            'access_token' => 'test_token',
            'webhook' => $webhookUrl,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->webhook('test_token', $webhookUrl);
    
    expect($result)->toHaveKey('request_id');
});

it('imports an item', function () {
    $products = ['transactions'];
    $userAuth = [
        'user_id' => 'user_123',
        'auth_token' => 'auth_token',
    ];
    
    $mockResponse = [
        'access_token' => 'imported-access-token',
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/item/import', [
            'products' => $products,
            'user_auth' => $userAuth,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->itemService->import($products, $userAuth);
    
    expect($result)->toHaveKey('access_token');
});