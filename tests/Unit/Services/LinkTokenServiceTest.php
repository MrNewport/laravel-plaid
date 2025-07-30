<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\LinkTokenService;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
    $this->linkTokenService = new LinkTokenService($this->mockClient);
});

afterEach(function () {
    Mockery::close();
});

it('creates a link token with minimal data', function () {
    $mockResponse = [
        'link_token' => 'link-sandbox-123456',
        'expiration' => '2024-01-01T12:00:00Z',
        'request_id' => 'test_request',
    ];
    
    $requestData = [
        'user' => ['client_user_id' => 'user-123'],
        'products' => ['transactions'],
    ];
    
    $expectedRequest = array_merge($requestData, [
        'client_name' => config('app.name'),
        'language' => 'en',
        'country_codes' => ['US'],
    ]);
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/link/token/create', $expectedRequest)
        ->andReturn($mockResponse);
    
    $result = $this->linkTokenService->create($requestData);
    
    expect($result)->toHaveKeys(['link_token', 'expiration', 'request_id']);
    expect($result['link_token'])->toBe('link-sandbox-123456');
});

it('creates a link token with all options', function () {
    $mockResponse = [
        'link_token' => 'link-sandbox-789',
        'expiration' => '2024-01-01T12:00:00Z',
        'request_id' => 'test_request',
    ];
    
    $requestData = [
        'user' => ['client_user_id' => 'user-456'],
        'client_name' => 'My App',
        'products' => ['auth', 'transactions'],
        'country_codes' => ['US', 'CA'],
        'language' => 'fr',
        'webhook' => 'https://example.com/webhook',
        'access_token' => 'existing_token',
        'link_customization_name' => 'custom',
        'redirect_uri' => 'https://app.com/redirect',
        'android_package_name' => 'com.example.app',
        'account_filters' => [
            'depository' => [
                'account_subtypes' => ['checking', 'savings'],
            ],
        ],
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/link/token/create', $requestData)
        ->andReturn($mockResponse);
    
    $result = $this->linkTokenService->create($requestData);
    
    expect($result['link_token'])->toBe('link-sandbox-789');
});

it('gets link token information', function () {
    $mockResponse = [
        'link_token' => 'link-sandbox-123',
        'created_at' => '2024-01-01T11:00:00Z',
        'expiration' => '2024-01-01T12:00:00Z',
        'metadata' => [
            'initial_products' => ['transactions'],
            'webhook' => 'https://example.com/webhook',
            'country_codes' => ['US'],
            'language' => 'en',
            'institution_id' => null,
            'redirect_uri' => null,
            'client_name' => 'Test App',
        ],
        'request_id' => 'test_request',
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/link/token/get', ['link_token' => 'link-sandbox-123'])
        ->andReturn($mockResponse);
    
    $result = $this->linkTokenService->get('link-sandbox-123');
    
    expect($result)->toHaveKeys(['link_token', 'created_at', 'expiration', 'metadata', 'request_id']);
    expect($result['metadata']['initial_products'])->toBe(['transactions']);
});

it('uses default config values when not provided', function () {
    config(['app.name' => 'Test App']);
    config(['plaid.defaults.language' => 'es']);
    config(['plaid.defaults.country_codes' => ['MX', 'US']]);
    
    $mockResponse = [
        'link_token' => 'link-sandbox-default',
        'expiration' => '2024-01-01T12:00:00Z',
        'request_id' => 'test_request',
    ];
    
    $requestData = [
        'user' => ['client_user_id' => 'user-789'],
        'products' => ['balance'],
    ];
    
    $expectedRequest = [
        'user' => ['client_user_id' => 'user-789'],
        'products' => ['balance'],
        'client_name' => 'Test App',
        'language' => 'es',
        'country_codes' => ['MX', 'US'],
    ];
    
    $this->mockClient->shouldReceive('post')
        ->once()
        ->with('/link/token/create', $expectedRequest)
        ->andReturn($mockResponse);
    
    $result = $this->linkTokenService->create($requestData);
    
    expect($result['link_token'])->toBe('link-sandbox-default');
});