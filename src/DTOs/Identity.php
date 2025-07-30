<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Identity extends BaseDTO
{
    public string $account_id;
    public array $addresses;
    public array $emails;
    public array $names;
    public array $phone_numbers;
}