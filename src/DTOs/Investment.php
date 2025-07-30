<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Investment extends BaseDTO
{
    public string $investment_transaction_id;
    public string $account_id;
    public ?string $security_id;
    public string $date;
    public string $name;
    public float $quantity;
    public float $amount;
    public float $price;
    public ?float $fees;
    public string $type;
    public string $subtype;
    public ?string $iso_currency_code;
    public ?string $unofficial_currency_code;
}