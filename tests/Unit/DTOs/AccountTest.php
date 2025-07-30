<?php

use MrNewport\LaravelPlaid\DTOs\Account;

it('initializes Account DTO with all properties', function () {
    $data = [
        'account_id' => 'test_account_123',
        'balances' => [
            'available' => 100.00,
            'current' => 110.00,
            'limit' => null,
            'iso_currency_code' => 'USD',
        ],
        'mask' => '0000',
        'name' => 'Test Checking',
        'official_name' => 'Test Checking Account',
        'type' => 'depository',
        'subtype' => 'checking',
        'verification_status' => 'verified',
        'persistent_account_id' => 'persistent_123',
    ];
    
    $account = new Account($data);
    
    expect($account->account_id)->toBe('test_account_123');
    expect($account->balances)->toBe($data['balances']);
    expect($account->mask)->toBe('0000');
    expect($account->name)->toBe('Test Checking');
    expect($account->official_name)->toBe('Test Checking Account');
    expect($account->type)->toBe('depository');
    expect($account->subtype)->toBe('checking');
    expect($account->verification_status)->toBe('verified');
    expect($account->persistent_account_id)->toBe('persistent_123');
});

it('handles partial Account data', function () {
    $data = [
        'account_id' => 'test_account_123',
        'name' => 'Test Account',
        'type' => 'depository',
        'subtype' => 'savings',
    ];
    
    $account = new Account($data);
    
    expect($account->account_id)->toBe('test_account_123');
    expect($account->name)->toBe('Test Account');
    expect($account->type)->toBe('depository');
    expect($account->subtype)->toBe('savings');
});

it('converts Account to array correctly', function () {
    $data = [
        'account_id' => 'test_account_123',
        'balances' => ['available' => 100.00],
        'mask' => '0000',
        'name' => 'Test Checking',
        'type' => 'depository',
        'subtype' => 'checking',
    ];
    
    $account = new Account($data);
    $array = $account->toArray();
    
    expect($array)->toMatchArray($data);
});

it('converts Account to JSON correctly', function () {
    $data = [
        'account_id' => 'test_account_123',
        'name' => 'Test Checking',
        'type' => 'depository',
        'subtype' => 'checking',
    ];
    
    $account = new Account($data);
    $json = $account->toJson();
    
    expect($json)->toBeJson();
    expect(json_decode($json, true))->toMatchArray($data);
});