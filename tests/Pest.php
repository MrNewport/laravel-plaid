<?php

use MrNewport\LaravelPlaid\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function mockPlaidResponse(array $data): array
{
    return array_merge([
        'request_id' => 'test_request_id_' . uniqid(),
    ], $data);
}