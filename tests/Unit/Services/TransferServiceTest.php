<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\TransferService;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->transferService = new TransferService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('creates a transfer', function () {
    $transferData = [
        'access_token' => 'test_token',
        'account_id' => 'account_123',
        'authorization_id' => 'auth_123',
        'type' => 'debit',
        'network' => 'ach',
        'amount' => '100.00',
        'description' => 'Test transfer',
        'ach_class' => 'ppd',
        'user' => ['legal_name' => 'John Doe'],
    ];
    
    $mockResponse = [
        'transfer' => [
            'id' => 'transfer_123',
            'status' => 'pending',
            'amount' => '100.00',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/create', $transferData)
        ->andReturn($mockResponse);
    
    $result = $this->transferService->create($transferData);
    
    expect($result)->toHaveKey('transfer');
    expect($result['transfer']['id'])->toBe('transfer_123');
});

it('gets a transfer', function () {
    $mockResponse = [
        'transfer' => [
            'id' => 'transfer_123',
            'status' => 'completed',
            'amount' => '100.00',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/get', ['transfer_id' => 'transfer_123'])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->get('transfer_123');
    
    expect($result['transfer']['status'])->toBe('completed');
});

it('lists transfers', function () {
    $mockResponse = [
        'transfers' => [
            ['id' => 'transfer_1', 'amount' => '50.00'],
            ['id' => 'transfer_2', 'amount' => '75.00'],
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/list', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'count' => 25,
            'offset' => 0,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->list('2024-01-01', '2024-01-31', 25, 0);
    
    expect($result['transfers'])->toHaveCount(2);
});

it('cancels a transfer', function () {
    $mockResponse = [
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/cancel', ['transfer_id' => 'transfer_123'])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->cancel('transfer_123');
    
    expect($result)->toHaveKey('request_id');
});

it('lists transfer events', function () {
    $mockResponse = [
        'events' => [
            ['event_id' => 1, 'event_type' => 'pending'],
            ['event_id' => 2, 'event_type' => 'completed'],
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/event/list', [
            'transfer_id' => 'transfer_123',
            'count' => 50,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->eventList(null, null, 'transfer_123', null, null, null, null, 50);
    
    expect($result['events'])->toHaveCount(2);
});

it('syncs transfer events', function () {
    $mockResponse = [
        'events' => [
            ['event_id' => 3, 'event_type' => 'failed'],
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/event/sync', [
            'after_id' => 'event_2',
            'count' => 25,
        ])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->eventSync('event_2', 25);
    
    expect($result['events'])->toHaveCount(1);
});

it('creates transfer authorization', function () {
    $authData = [
        'access_token' => 'test_token',
        'account_id' => 'account_123',
        'type' => 'debit',
        'network' => 'ach',
        'amount' => '100.00',
        'ach_class' => 'ppd',
        'user' => ['legal_name' => 'John Doe'],
    ];
    
    $mockResponse = [
        'authorization' => [
            'id' => 'auth_123',
            'created' => '2024-01-15T10:00:00Z',
            'decision' => 'approved',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/authorization/create', $authData)
        ->andReturn($mockResponse);
    
    $result = $this->transferService->authorizationCreate($authData);
    
    expect($result['authorization']['decision'])->toBe('approved');
});

it('creates transfer intent', function () {
    $intentData = [
        'account_id' => 'account_123',
        'mode' => 'PAYMENT',
        'amount' => '100.00',
        'description' => 'Test payment',
        'ach_class' => 'ppd',
        'user' => ['legal_name' => 'John Doe'],
    ];
    
    $mockResponse = [
        'transfer_intent' => [
            'id' => 'intent_123',
            'created' => '2024-01-15T10:00:00Z',
            'status' => 'pending',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/intent/create', $intentData)
        ->andReturn($mockResponse);
    
    $result = $this->transferService->intentCreate($intentData);
    
    expect($result['transfer_intent']['id'])->toBe('intent_123');
});

it('gets transfer intent', function () {
    $mockResponse = [
        'transfer_intent' => [
            'id' => 'intent_123',
            'status' => 'succeeded',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/transfer/intent/get', ['transfer_intent_id' => 'intent_123'])
        ->andReturn($mockResponse);
    
    $result = $this->transferService->intentGet('intent_123');
    
    expect($result['transfer_intent']['status'])->toBe('succeeded');
});