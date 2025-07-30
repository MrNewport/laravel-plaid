<?php

use MrNewport\LaravelPlaid\DTOs\AccountNumbers;

it('initializes AccountNumbers DTO with all arrays', function () {
    $data = [
        'ach' => [
            ['account' => '1111222233330000', 'routing' => '011401533'],
        ],
        'eft' => [
            ['account' => '111122223333', 'institution' => '021', 'branch' => '01140'],
        ],
        'international' => [
            ['iban' => 'GB29NWBK60161331926819', 'bic' => 'NWBKGB2L'],
        ],
        'bacs' => [
            ['account' => '31926819', 'sort_code' => '601613'],
        ],
    ];
    
    $accountNumbers = new AccountNumbers($data);
    
    expect($accountNumbers->ach)->toBe($data['ach']);
    expect($accountNumbers->eft)->toBe($data['eft']);
    expect($accountNumbers->international)->toBe($data['international']);
    expect($accountNumbers->bacs)->toBe($data['bacs']);
});

it('initializes with empty arrays when data not provided', function () {
    $accountNumbers = new AccountNumbers([]);
    
    expect($accountNumbers->ach)->toBe([]);
    expect($accountNumbers->eft)->toBe([]);
    expect($accountNumbers->international)->toBe([]);
    expect($accountNumbers->bacs)->toBe([]);
});

it('handles partial data correctly', function () {
    $data = [
        'ach' => [
            ['account' => '1111222233330000', 'routing' => '011401533'],
        ],
    ];
    
    $accountNumbers = new AccountNumbers($data);
    
    expect($accountNumbers->ach)->toBe($data['ach']);
    expect($accountNumbers->eft)->toBe([]);
    expect($accountNumbers->international)->toBe([]);
    expect($accountNumbers->bacs)->toBe([]);
});

it('converts to array correctly', function () {
    $data = [
        'ach' => [
            ['account' => '1111222233330000', 'routing' => '011401533'],
        ],
        'eft' => [],
        'international' => [],
        'bacs' => [],
    ];
    
    $accountNumbers = new AccountNumbers($data);
    $array = $accountNumbers->toArray();
    
    expect($array)->toBe($data);
});