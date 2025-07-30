<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Transfer extends BaseDTO
{
    public string $id;
    public ?string $ach_class;
    public string $account_id;
    public string $amount;
    public ?string $cancellable;
    public string $created;
    public ?string $description;
    public ?array $failure_reason;
    public ?string $iso_currency_code;
    public ?array $metadata;
    public string $network;
    public ?string $origination_account_id;
    public ?string $standard_return_window;
    public string $status;
    public ?array $sweep;
    public string $type;
    public array $user;
    public ?string $authorization_id;
    public ?string $credit_funds_source;
}