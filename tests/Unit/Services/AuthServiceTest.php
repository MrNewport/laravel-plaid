<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\AuthService;
use MrNewport\LaravelPlaid\DTOs\AccountNumbers;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->authService = new AuthService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('gets auth data successfully', function () {
    $mockResponse = [
        'accounts' => [
            [
                'account_id' => 'test_account_1',
                'balances' => ['available' => 100.00],
                'name' => 'Test Checking',
            ],
        ],
        'numbers' => [
            'ach' => [
                ['account' => '1111222233330000', 'routing' => '011401533'],
            ],
            'eft' => [],
            'international' => [],
            'bacs' => [],
        ],
        'item' => ['item_id' => 'test_item'],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/auth/get', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->authService->get('test_token');
    
    expect($result)->toHaveKeys(['accounts', 'numbers', 'item', 'request_id']);
    expect($result['accounts'])->toHaveCount(1);
    expect($result['numbers'])->toBeInstanceOf(AccountNumbers::class);
    expect($result['numbers']->ach)->toHaveCount(1);
    expect($result['numbers']->ach[0]['routing'])->toBe('011401533');
});

it('gets auth data with specific account IDs', function () {
    $accountIds = ['account1', 'account2'];
    
    $mockResponse = [
        'accounts' => [],
        'numbers' => ['ach' => [], 'eft' => [], 'international' => [], 'bacs' => []],
        'item' => ['item_id' => 'test_item'],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/auth/get', [
            'access_token' => 'test_token',
            'account_ids' => $accountIds,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->authService->get('test_token', $accountIds);
    
    expect($result['numbers'])->toBeInstanceOf(AccountNumbers::class);
});

it('gets auth data with options', function () {
    $options = ['account_ids_with_updated_auth' => ['account1']];
    
    $mockResponse = [
        'accounts' => [],
        'numbers' => ['ach' => [], 'eft' => [], 'international' => [], 'bacs' => []],
        'item' => ['item_id' => 'test_item'],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/auth/get', [
            'access_token' => 'test_token',
            'options' => $options,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->authService->get('test_token', null, $options);
    
    expect($result)->toHaveKeys(['accounts', 'numbers', 'item', 'request_id']);
});