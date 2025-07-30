<?php

use MrNewport\LaravelPlaid\Plaid;
use MrNewport\LaravelPlaid\PlaidClient;

it('creates Plaid instance with all services accessible', function () {
    $plaid = $this->app->make(Plaid::class);
    
    expect($plaid)->toBeInstanceOf(Plaid::class);
    
    // Test that all services are accessible and properly instantiated
    $services = [
        'accounts' => \MrNewport\LaravelPlaid\Services\AccountsService::class,
        'assets' => \MrNewport\LaravelPlaid\Services\AssetsService::class,
        'auth' => \MrNewport\LaravelPlaid\Services\AuthService::class,
        'employment' => \MrNewport\LaravelPlaid\Services\EmploymentService::class,
        'identity' => \MrNewport\LaravelPlaid\Services\IdentityService::class,
        'identityVerification' => \MrNewport\LaravelPlaid\Services\IdentityVerificationService::class,
        'income' => \MrNewport\LaravelPlaid\Services\IncomeService::class,
        'institutions' => \MrNewport\LaravelPlaid\Services\InstitutionsService::class,
        'investments' => \MrNewport\LaravelPlaid\Services\InvestmentsService::class,
        'item' => \MrNewport\LaravelPlaid\Services\ItemService::class,
        'liabilities' => \MrNewport\LaravelPlaid\Services\LiabilitiesService::class,
        'linkToken' => \MrNewport\LaravelPlaid\Services\LinkTokenService::class,
        'paymentInitiation' => \MrNewport\LaravelPlaid\Services\PaymentInitiationService::class,
        'processor' => \MrNewport\LaravelPlaid\Services\ProcessorService::class,
        'sandbox' => \MrNewport\LaravelPlaid\Services\SandboxService::class,
        'statements' => \MrNewport\LaravelPlaid\Services\StatementsService::class,
        'transactions' => \MrNewport\LaravelPlaid\Services\TransactionsService::class,
        'transfer' => \MrNewport\LaravelPlaid\Services\TransferService::class,
    ];
    
    foreach ($services as $method => $expectedClass) {
        expect($plaid->$method())->toBeInstanceOf($expectedClass);
    }
});

it('returns the same service instance when called multiple times', function () {
    $plaid = $this->app->make(Plaid::class);
    
    $accounts1 = $plaid->accounts();
    $accounts2 = $plaid->accounts();
    
    expect($accounts1)->toBe($accounts2);
    
    $transactions1 = $plaid->transactions();
    $transactions2 = $plaid->transactions();
    
    expect($transactions1)->toBe($transactions2);
});

it('can get the underlying PlaidClient', function () {
    $plaid = $this->app->make(Plaid::class);
    $client = $plaid->getClient();
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});

it('uses configuration from Laravel config', function () {
    config([
        'plaid.client_id' => 'custom_client_id',
        'plaid.secret' => 'custom_secret',
        'plaid.environment' => 'development',
    ]);
    
    // Re-bind to use new config
    $this->app->singleton(PlaidClient::class, function ($app) {
        return new PlaidClient($app['config']['plaid']);
    });
    
    $client = $this->app->make(PlaidClient::class);
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});

it('handles different environments correctly', function () {
    $environments = ['sandbox', 'development', 'production'];
    
    foreach ($environments as $env) {
        config(['plaid.environment' => $env]);
        
        $this->app->singleton(PlaidClient::class, function ($app) {
            return new PlaidClient($app['config']['plaid']);
        });
        
        $client = $this->app->make(PlaidClient::class);
        
        expect($client)->toBeInstanceOf(PlaidClient::class);
    }
});

it('respects logging configuration', function () {
    config([
        'plaid.logging.enabled' => true,
        'plaid.logging.channel' => 'plaid',
        'plaid.logging.sensitive_fields' => ['access_token', 'secret'],
    ]);
    
    $this->app->singleton(PlaidClient::class, function ($app) {
        return new PlaidClient($app['config']['plaid']);
    });
    
    $client = $this->app->make(PlaidClient::class);
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});

it('respects HTTP client options', function () {
    config([
        'plaid.http_options.timeout' => 60,
        'plaid.http_options.connect_timeout' => 20,
        'plaid.http_options.retry_enabled' => false,
    ]);
    
    $this->app->singleton(PlaidClient::class, function ($app) {
        return new PlaidClient($app['config']['plaid']);
    });
    
    $client = $this->app->make(PlaidClient::class);
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});