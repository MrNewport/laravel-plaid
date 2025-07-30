<?php

use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\IdentityService;
use MrNewport\LaravelPlaid\Services\InvestmentsService;
use MrNewport\LaravelPlaid\Services\LiabilitiesService;
use MrNewport\LaravelPlaid\Services\ProcessorService;
use MrNewport\LaravelPlaid\Services\InstitutionsService;
use MrNewport\LaravelPlaid\Services\SandboxService;
use MrNewport\LaravelPlaid\Services\AssetsService;
use MrNewport\LaravelPlaid\Services\IncomeService;
use MrNewport\LaravelPlaid\Services\EmploymentService;
use MrNewport\LaravelPlaid\Services\StatementsService;
use MrNewport\LaravelPlaid\Services\PaymentInitiationService;
use MrNewport\LaravelPlaid\Services\IdentityVerificationService;

beforeEach(function () {
    $this->mockClient = Mockery::mock(PlaidClient::class);
});

afterEach(function () {
    Mockery::close();
});

describe('IdentityService', function () {
    it('gets identity data', function () {
        $service = new IdentityService($this->mockClient);
        
        $mockResponse = [
            'accounts' => [],
            'item' => ['item_id' => 'item_123'],
            'request_id' => 'test',
        ];
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/identity/get', ['access_token' => 'test_token'])
            ->andReturn($mockResponse);
        
        $result = $service->get('test_token');
        expect($result)->toHaveKey('request_id');
    });
    
    it('matches identity data', function () {
        $service = new IdentityService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/identity/match', ['access_token' => 'test_token'])
            ->andReturn(['request_id' => 'test']);
        
        $result = $service->match('test_token');
        expect($result)->toHaveKey('request_id');
    });
});

describe('InvestmentsService', function () {
    it('gets investment holdings', function () {
        $service = new InvestmentsService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/investments/holdings/get', ['access_token' => 'test_token'])
            ->andReturn(['holdings' => [], 'request_id' => 'test']);
        
        $result = $service->holdingsGet('test_token');
        expect($result)->toHaveKey('holdings');
    });
    
    it('gets investment transactions', function () {
        $service = new InvestmentsService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/investments/transactions/get', [
                'access_token' => 'test_token',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ])
            ->andReturn(['investment_transactions' => [], 'request_id' => 'test']);
        
        $result = $service->transactionsGet('test_token', '2024-01-01', '2024-01-31');
        expect($result)->toHaveKey('investment_transactions');
    });
});

describe('ProcessorService', function () {
    it('creates processor token', function () {
        $service = new ProcessorService($this->mockClient);
        
        $mockResponse = [
            'processor_token' => 'processor-sandbox-123',
            'request_id' => 'test',
        ];
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/processor/token/create', [
                'access_token' => 'test_token',
                'account_id' => 'account_123',
                'processor' => 'dwolla',
            ])
            ->andReturn($mockResponse);
        
        $result = $service->tokenCreate('test_token', 'account_123', 'dwolla');
        expect($result['processor_token'])->toBe('processor-sandbox-123');
    });
    
    it('creates stripe token', function () {
        $service = new ProcessorService($this->mockClient);
        
        $mockResponse = [
            'stripe_bank_account_token' => 'btok_123',
            'request_id' => 'test',
        ];
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/processor/stripe/bank_account_token/create', [
                'access_token' => 'test_token',
                'account_id' => 'account_123',
            ])
            ->andReturn($mockResponse);
        
        $result = $service->stripeTokenCreate('test_token', 'account_123');
        expect($result['stripe_bank_account_token'])->toBe('btok_123');
    });
});

describe('InstitutionsService', function () {
    it('searches institutions', function () {
        $service = new InstitutionsService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/institutions/search', [
                'query' => 'chase',
                'country_codes' => ['US'],
                'products' => ['transactions'],
            ])
            ->andReturn(['institutions' => []]);
        
        $result = $service->search('chase', ['US'], ['transactions']);
        expect($result)->toHaveKey('institutions');
    });
});

describe('AssetsService', function () {
    it('creates asset report', function () {
        $service = new AssetsService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/asset_report/create', [
                'access_tokens' => ['token1', 'token2'],
                'days_requested' => 90,
            ])
            ->andReturn(['asset_report_token' => 'asset-sandbox-123']);
        
        $result = $service->reportCreate(['token1', 'token2'], 90);
        expect($result)->toHaveKey('asset_report_token');
    });
    
    it('gets asset report PDF', function () {
        $service = new AssetsService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/asset_report/pdf/get', ['asset_report_token' => 'token'])
            ->andReturn(['pdf' => base64_encode('PDF content')]);
        
        $result = $service->reportPdfGet('token');
        expect($result)->toBe('PDF content');
    });
});

describe('SandboxService', function () {
    it('creates public token in sandbox', function () {
        $service = new SandboxService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/sandbox/public_token/create', [
                'institution_id' => 'ins_123',
                'initial_products' => ['transactions'],
            ])
            ->andReturn(['public_token' => 'public-sandbox-123']);
        
        $result = $service->publicTokenCreate('ins_123', ['transactions']);
        expect($result)->toHaveKey('public_token');
    });
    
    it('fires webhook in sandbox', function () {
        $service = new SandboxService($this->mockClient);
        
        $this->mockClient->shouldReceive('post')
            ->once()
            ->with('/sandbox/item/fire_webhook', [
                'access_token' => 'test_token',
                'webhook_code' => 'DEFAULT_UPDATE',
            ])
            ->andReturn(['webhook_sent' => true]);
        
        $result = $service->itemFireWebhook('test_token', 'DEFAULT_UPDATE');
        expect($result)->toHaveKey('webhook_sent');
    });
});