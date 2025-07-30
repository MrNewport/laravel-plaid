<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\TransactionsService;
use MrNewport\LaravelPlaid\DTOs\Transaction;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->transactionsService = new TransactionsService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('syncs transactions successfully', function () {
    $mockResponse = [
        'added' => [
            [
                'account_id' => 'account_123',
                'amount' => 50.00,
                'category' => ['Food and Drink'],
                'date' => '2024-01-15',
                'is_pending' => false,
                'transaction_id' => 'trans_1',
            ],
        ],
        'modified' => [
            [
                'account_id' => 'account_123',
                'amount' => 75.00,
                'category' => ['Shopping'],
                'date' => '2024-01-14',
                'is_pending' => false,
                'transaction_id' => 'trans_2',
            ],
        ],
        'removed' => [
            ['transaction_id' => 'trans_3'],
        ],
        'next_cursor' => 'cursor_123',
        'has_more' => true,
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/sync', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->sync('test_token');
    
    expect($result)->toHaveKeys(['added', 'modified', 'removed', 'next_cursor', 'has_more', 'request_id']);
    expect($result['added'])->toHaveCount(1);
    expect($result['added'][0])->toBeInstanceOf(Transaction::class);
    expect($result['modified'])->toHaveCount(1);
    expect($result['modified'][0])->toBeInstanceOf(Transaction::class);
    expect($result['removed'])->toHaveCount(1);
    expect($result['has_more'])->toBeTrue();
});

it('syncs transactions with cursor and options', function () {
    $cursor = 'previous_cursor';
    $count = 50;
    $options = ['include_personal_finance_category' => true];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/sync', [
            'access_token' => 'test_token',
            'cursor' => $cursor,
            'count' => $count,
            'options' => $options,
        ])
        ->andReturn([
            'added' => [],
            'modified' => [],
            'removed' => [],
            'next_cursor' => 'new_cursor',
            'has_more' => false,
            'request_id' => 'test',
        ]);
    
    $this->transactionsService->sync('test_token', $cursor, $count, $options);
});

it('gets transactions with date range', function () {
    $mockResponse = [
        'accounts' => [['account_id' => 'acc_123']],
        'transactions' => [
            [
                'account_id' => 'acc_123',
                'amount' => 100.00,
                'category' => ['Transfer'],
                'date' => '2024-01-15',
                'is_pending' => false,
                'transaction_id' => 'trans_1',
            ],
        ],
        'item' => ['item_id' => 'item_123'],
        'total_transactions' => 1,
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/get', [
            'access_token' => 'test_token',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->get('test_token', '2024-01-01', '2024-01-31');
    
    expect($result['transactions'])->toHaveCount(1);
    expect($result['transactions'][0])->toBeInstanceOf(Transaction::class);
    expect($result['total_transactions'])->toBe(1);
});

it('refreshes transactions', function () {
    $mockResponse = ['request_id' => 'test_request'];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/refresh', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->refresh('test_token');
    
    expect($result)->toHaveKey('request_id');
});

it('gets recurring transactions', function () {
    $mockResponse = [
        'inflow_streams' => [
            ['stream_id' => 'stream_1', 'description' => 'Salary'],
        ],
        'outflow_streams' => [
            ['stream_id' => 'stream_2', 'description' => 'Rent'],
        ],
        'updated_datetime' => '2024-01-15T10:00:00Z',
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/recurring/get', ['access_token' => 'test_token'])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->recurring('test_token');
    
    expect($result)->toHaveKeys(['inflow_streams', 'outflow_streams', 'updated_datetime', 'request_id']);
});

it('enriches transactions', function () {
    $transactions = [
        ['description' => 'AMAZON PURCHASE', 'amount' => 50.00],
    ];
    
    $mockResponse = [
        'enriched_transactions' => [
            ['merchant_name' => 'Amazon', 'category' => ['Shopping']],
        ],
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/enrich', ['transactions' => $transactions])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->enrich($transactions);
    
    expect($result)->toHaveKey('enriched_transactions');
});

it('categorizes transactions', function () {
    $query = 'Coffee Shop Purchase';
    $options = ['personal_finance_category' => true];
    
    $mockResponse = [
        'category' => ['Food and Drink', 'Coffee Shops'],
        'category_id' => '13005043',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transactions/categorize', [
            'query' => $query,
            'options' => $options,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->transactionsService->categorize($query, $options);
    
    expect($result)->toHaveKeys(['category', 'category_id']);
});