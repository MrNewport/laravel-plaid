<?php

use MrNewport\LaravelPlaid\DTOs\AccountBalance;
use MrNewport\LaravelPlaid\DTOs\Identity;
use MrNewport\LaravelPlaid\DTOs\Institution;
use MrNewport\LaravelPlaid\DTOs\Investment;
use MrNewport\LaravelPlaid\DTOs\Liability;
use MrNewport\LaravelPlaid\DTOs\LinkToken;
use MrNewport\LaravelPlaid\DTOs\Transfer;

it('initializes AccountBalance DTO correctly', function () {
    $data = [
        'account_id' => 'acc_123',
        'balances' => ['available' => 1000.00, 'current' => 1100.00],
        'mask' => '0000',
        'name' => 'Checking',
        'official_name' => 'Checking Account',
        'persistent_account_id' => 'persistent_123',
        'subtype' => 'checking',
        'type' => 'depository',
    ];
    
    $balance = new AccountBalance($data);
    
    expect($balance->account_id)->toBe('acc_123');
    expect($balance->balances)->toBe($data['balances']);
    expect($balance->name)->toBe('Checking');
});

it('initializes Identity DTO correctly', function () {
    $data = [
        'account_id' => 'acc_123',
        'addresses' => [
            ['primary' => true, 'data' => ['city' => 'New York']],
        ],
        'emails' => [
            ['primary' => true, 'data' => 'john@example.com'],
        ],
        'names' => ['John Doe'],
        'phone_numbers' => [
            ['primary' => true, 'data' => '+1234567890'],
        ],
    ];
    
    $identity = new Identity($data);
    
    expect($identity->account_id)->toBe('acc_123');
    expect($identity->addresses)->toBe($data['addresses']);
    expect($identity->emails)->toBe($data['emails']);
    expect($identity->names)->toBe(['John Doe']);
});

it('initializes Institution DTO correctly', function () {
    $data = [
        'institution_id' => 'ins_123',
        'name' => 'Test Bank',
        'products' => ['assets', 'auth', 'balance'],
        'country_codes' => ['US', 'CA'],
        'url' => 'https://testbank.com',
        'primary_color' => '#1f1f1f',
        'logo' => 'base64_encoded_logo',
        'routing_numbers' => ['021000021'],
        'oauth' => true,
        'status' => ['item_logins' => ['status' => 'HEALTHY']],
        'payment_initiation_metadata' => null,
        'auth_metadata' => null,
    ];
    
    $institution = new Institution($data);
    
    expect($institution->institution_id)->toBe('ins_123');
    expect($institution->name)->toBe('Test Bank');
    expect($institution->products)->toBe(['assets', 'auth', 'balance']);
    expect($institution->oauth)->toBeTrue();
});

it('initializes Investment DTO correctly', function () {
    $data = [
        'investment_transaction_id' => 'inv_123',
        'account_id' => 'acc_123',
        'security_id' => 'sec_123',
        'date' => '2024-01-15',
        'name' => 'Apple Inc.',
        'quantity' => 10.0,
        'amount' => 1500.00,
        'price' => 150.00,
        'fees' => 5.00,
        'type' => 'buy',
        'subtype' => 'buy',
        'iso_currency_code' => 'USD',
        'unofficial_currency_code' => null,
    ];
    
    $investment = new Investment($data);
    
    expect($investment->investment_transaction_id)->toBe('inv_123');
    expect($investment->name)->toBe('Apple Inc.');
    expect($investment->quantity)->toBe(10.0);
    expect($investment->amount)->toBe(1500.00);
});

it('initializes Liability DTO correctly', function () {
    $data = [
        'account_id' => 'acc_123',
        'aprs' => [
            ['apr_percentage' => 15.99, 'apr_type' => 'purchase'],
        ],
        'last_payment_date' => '2024-01-01',
        'last_payment_amount' => 100.00,
        'last_statement_issue_date' => '2024-01-15',
        'last_statement_balance' => 1000.00,
        'minimum_payment_amount' => 25.00,
        'next_payment_due_date' => '2024-02-01',
        'origination_date' => '2023-01-01',
        'principal' => 5000.00,
    ];
    
    $liability = new Liability($data);
    
    expect($liability->account_id)->toBe('acc_123');
    expect($liability->last_payment_amount)->toBe(100.00);
    expect($liability->principal)->toBe(5000.00);
});

it('initializes LinkToken DTO correctly', function () {
    $data = [
        'link_token' => 'link-sandbox-123456',
        'expiration' => '2024-01-01T12:00:00Z',
        'request_id' => 'req_123',
    ];
    
    $linkToken = new LinkToken($data);
    
    expect($linkToken->link_token)->toBe('link-sandbox-123456');
    expect($linkToken->expiration)->toBe('2024-01-01T12:00:00Z');
    expect($linkToken->request_id)->toBe('req_123');
});

it('initializes Transfer DTO correctly', function () {
    $data = [
        'id' => 'transfer_123',
        'ach_class' => 'ppd',
        'account_id' => 'acc_123',
        'amount' => '100.00',
        'cancellable' => true,
        'created' => '2024-01-15T10:00:00Z',
        'description' => 'Test transfer',
        'failure_reason' => null,
        'iso_currency_code' => 'USD',
        'metadata' => ['order_id' => '12345'],
        'network' => 'ach',
        'origination_account_id' => 'orig_123',
        'standard_return_window' => '2024-01-17',
        'status' => 'pending',
        'sweep' => null,
        'type' => 'debit',
        'user' => ['legal_name' => 'John Doe'],
        'authorization_id' => 'auth_123',
        'credit_funds_source' => 'sweep',
    ];
    
    $transfer = new Transfer($data);
    
    expect($transfer->id)->toBe('transfer_123');
    expect($transfer->amount)->toBe('100.00');
    expect($transfer->status)->toBe('pending');
    expect($transfer->type)->toBe('debit');
});