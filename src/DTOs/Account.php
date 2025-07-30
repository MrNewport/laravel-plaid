<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Account extends BaseDTO
{
    public string $account_id;
    public ?array $balances;
    public ?string $mask;
    public string $name;
    public ?string $official_name;
    public string $type;
    public string $subtype;
    public ?string $verification_status;
    public ?string $persistent_account_id;
}