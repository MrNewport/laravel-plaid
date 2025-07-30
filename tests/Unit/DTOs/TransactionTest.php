<?php

use MrNewport\LaravelPlaid\DTOs\Transaction;

it('initializes Transaction DTO with all properties', function () {
    $data = [
        'account_id' => 'account_123',
        'account_owner' => 'John Doe',
        'amount' => 50.25,
        'authorized_date' => '2024-01-15',
        'authorized_datetime' => '2024-01-15T10:30:00Z',
        'category' => ['Food and Drink', 'Restaurants'],
        'category_id' => '13005000',
        'check_number' => '1234',
        'counterparties' => [
            ['name' => 'Restaurant ABC', 'type' => 'merchant'],
        ],
        'date' => '2024-01-15',
        'datetime' => '2024-01-15T10:30:00Z',
        'is_pending' => false,
        'location' => [
            'address' => '123 Main St',
            'city' => 'New York',
            'region' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
            'lat' => 40.7128,
            'lon' => -74.0060,
        ],
        'logo_url' => 'https://example.com/logo.png',
        'merchant_entity_id' => 'merchant_123',
        'merchant_name' => 'Restaurant ABC',
        'name' => 'Restaurant ABC',
        'original_description' => 'RESTAURANT ABC NEW YORK',
        'payment_meta' => [
            'reference_number' => 'ref123',
            'ppd_id' => 'ppd123',
        ],
        'payment_channel' => 'in store',
        'pending_transaction_id' => null,
        'personal_finance_category' => [
            'primary' => 'FOOD_AND_DRINK',
            'detailed' => 'FOOD_AND_DRINK_RESTAURANTS',
        ],
        'personal_finance_category_icon_url' => 'https://example.com/icon.png',
        'transaction_code' => 'purchase',
        'transaction_id' => 'transaction_123',
        'transaction_type' => 'place',
        'unofficial_currency_code' => null,
        'website' => 'https://restaurantabc.com',
    ];
    
    $transaction = new Transaction($data);
    
    expect($transaction->account_id)->toBe('account_123');
    expect($transaction->amount)->toBe(50.25);
    expect($transaction->category)->toBe(['Food and Drink', 'Restaurants']);
    expect($transaction->is_pending)->toBeFalse();
    expect($transaction->location)->toBe($data['location']);
    expect($transaction->merchant_name)->toBe('Restaurant ABC');
    expect($transaction->transaction_id)->toBe('transaction_123');
});

it('handles minimal Transaction data', function () {
    $data = [
        'account_id' => 'account_123',
        'amount' => 100.00,
        'category' => [],
        'date' => '2024-01-15',
        'is_pending' => true,
        'transaction_id' => 'transaction_123',
    ];
    
    $transaction = new Transaction($data);
    
    expect($transaction->account_id)->toBe('account_123');
    expect($transaction->amount)->toBe(100.00);
    expect($transaction->is_pending)->toBeTrue();
});

it('converts Transaction to array correctly', function () {
    $data = [
        'account_id' => 'account_123',
        'amount' => 50.25,
        'category' => ['Food and Drink'],
        'date' => '2024-01-15',
        'is_pending' => false,
        'name' => 'Test Transaction',
        'transaction_id' => 'transaction_123',
    ];
    
    $transaction = new Transaction($data);
    $array = $transaction->toArray();
    
    expect($array)->toMatchArray($data);
});