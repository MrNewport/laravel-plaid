<?php

namespace MrNewport\LaravelPlaid\DTOs;

class AccountBalance extends BaseDTO
{
    public string $account_id;
    public array $balances;
    public ?string $mask;
    public string $name;
    public ?string $official_name;
    public ?string $persistent_account_id;
    public ?string $subtype;
    public ?string $type;
}