<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\AccountsService;
use MrNewport\LaravelPlaid\DTOs\Account;
use MrNewport\LaravelPlaid\DTOs\AccountBalance;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->accountsService = new AccountsService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('gets accounts successfully', function () {
    $mockResponse = [
        'accounts' => [
            [
                'account_id' => 'test_account_1',
                'balances' => ['available' => 100.00, 'current' => 110.00],
                'mask' => '0000',
                'name' => 'Test Checking',
                'official_name' => 'Test Checking Account',
                'type' => 'depository',
                'subtype' => 'checking',
            ],
            [
                'account_id' => 'test_account_2',
                'balances' => ['available' => 500.00, 'current' => 500.00],
                'mask' => '1111',
                'name' => 'Test Savings',
                'type' => 'depository',
                'subtype' => 'savings',
            ],
        ],
        'item' => ['item_id' => 'test_item'],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/accounts/get', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->accountsService->get('test_token');
    
    expect($result)->toHaveKeys(['accounts', 'item', 'request_id']);
    expect($result['accounts'])->toHaveCount(2);
    expect($result['accounts'][0])->toBeInstanceOf(Account::class);
    expect($result['accounts'][0]->account_id)->toBe('test_account_1');
    expect($result['accounts'][1])->toBeInstanceOf(Account::class);
    expect($result['accounts'][1]->account_id)->toBe('test_account_2');
});

it('gets accounts with specific account IDs', function () {
    $accountIds = ['account1', 'account2'];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/accounts/get', [
            'access_token' => 'test_token',
            'account_ids' => $accountIds,
        ])
        ->andReturn(['accounts' => [], 'item' => [], 'request_id' => 'test']);
    
    $this->accountsService->get('test_token', $accountIds);
});

it('gets accounts with options', function () {
    $options = ['include_personal_finance_category' => true];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/accounts/get', [
            'access_token' => 'test_token',
            'options' => $options,
        ])
        ->andReturn(['accounts' => [], 'item' => [], 'request_id' => 'test']);
    
    $this->accountsService->get('test_token', null, $options);
});

it('gets account balances', function () {
    $mockResponse = [
        'accounts' => [
            [
                'account_id' => 'test_account_1',
                'balances' => ['available' => 100.00, 'current' => 110.00],
                'mask' => '0000',
                'name' => 'Test Checking',
            ],
        ],
        'item' => ['item_id' => 'test_item'],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/accounts/balance/get', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->accountsService->getBalance('test_token');
    
    expect($result)->toHaveKeys(['accounts', 'item', 'request_id']);
    expect($result['accounts'])->toHaveCount(1);
    expect($result['accounts'][0])->toBeInstanceOf(AccountBalance::class);
    expect($result['accounts'][0]->account_id)->toBe('test_account_1');
});

it('gets account balances with options', function () {
    $accountIds = ['account1'];
    $options = ['min_last_updated_datetime' => '2024-01-01T00:00:00Z'];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/accounts/balance/get', [
            'access_token' => 'test_token',
            'account_ids' => $accountIds,
            'options' => $options,
        ])
        ->andReturn(['accounts' => [], 'item' => [], 'request_id' => 'test']);
    
    $this->accountsService->getBalance('test_token', $accountIds, $options);
});