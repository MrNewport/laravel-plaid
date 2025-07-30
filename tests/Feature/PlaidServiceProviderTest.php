<?php

use MrNewport\LaravelPlaid\Facades\Plaid as PlaidFacade;
use MrNewport\LaravelPlaid\Plaid;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\PlaidServiceProvider;

it('registers the service provider', function () {
    expect($this->app->providerIsLoaded(PlaidServiceProvider::class))->toBeTrue();
});

it('binds PlaidClient as singleton', function () {
    $client1 = $this->app->make(PlaidClient::class);
    $client2 = $this->app->make(PlaidClient::class);
    
    expect($client1)->toBeInstanceOf(PlaidClient::class);
    expect($client1)->toBe($client2);
});

it('binds Plaid as singleton', function () {
    $plaid1 = $this->app->make(Plaid::class);
    $plaid2 = $this->app->make(Plaid::class);
    
    expect($plaid1)->toBeInstanceOf(Plaid::class);
    expect($plaid1)->toBe($plaid2);
});

it('binds plaid alias', function () {
    $plaid = $this->app->make('plaid');
    
    expect($plaid)->toBeInstanceOf(Plaid::class);
});

it('loads configuration file', function () {
    $config = $this->app->make('config')->get('plaid');
    
    expect($config)->toBeArray();
    expect($config)->toHaveKeys([
        'client_id',
        'secret',
        'environment',
        'version',
        'urls',
        'http_options',
        'webhooks',
        'defaults',
        'logging',
    ]);
});

it('uses environment variables for configuration', function () {
    $config = $this->app->make('config')->get('plaid');
    
    expect($config['client_id'])->toBe('test_client_id');
    expect($config['secret'])->toBe('test_secret');
    expect($config['environment'])->toBe('sandbox');
});

it('provides all expected services', function () {
    $provider = new PlaidServiceProvider($this->app);
    $provides = $provider->provides();
    
    expect($provides)->toContain(PlaidClient::class);
    expect($provides)->toContain(Plaid::class);
    expect($provides)->toContain('plaid');
});

it('facade resolves to Plaid instance', function () {
    $facadeRoot = PlaidFacade::getFacadeRoot();
    
    expect($facadeRoot)->toBeInstanceOf(Plaid::class);
});

it('facade can access all services', function () {
    expect(PlaidFacade::accounts())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\AccountsService::class);
    expect(PlaidFacade::auth())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\AuthService::class);
    expect(PlaidFacade::transactions())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\TransactionsService::class);
    expect(PlaidFacade::linkToken())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\LinkTokenService::class);
    expect(PlaidFacade::item())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\ItemService::class);
    expect(PlaidFacade::identity())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\IdentityService::class);
    expect(PlaidFacade::investments())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\InvestmentsService::class);
    expect(PlaidFacade::liabilities())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\LiabilitiesService::class);
    expect(PlaidFacade::assets())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\AssetsService::class);
    expect(PlaidFacade::employment())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\EmploymentService::class);
    expect(PlaidFacade::income())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\IncomeService::class);
    expect(PlaidFacade::transfer())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\TransferService::class);
    expect(PlaidFacade::paymentInitiation())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\PaymentInitiationService::class);
    expect(PlaidFacade::processor())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\ProcessorService::class);
    expect(PlaidFacade::institutions())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\InstitutionsService::class);
    expect(PlaidFacade::sandbox())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\SandboxService::class);
    expect(PlaidFacade::statements())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\StatementsService::class);
    expect(PlaidFacade::identityVerification())->toBeInstanceOf(\MrNewport\LaravelPlaid\Services\IdentityVerificationService::class);
});

it('facade can get client instance', function () {
    $client = PlaidFacade::getClient();
    
    expect($client)->toBeInstanceOf(PlaidClient::class);
});