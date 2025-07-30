<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Transaction extends BaseDTO
{
    public string $account_id;
    public ?string $account_owner;
    public float $amount;
    public ?string $authorized_date;
    public ?string $authorized_datetime;
    public array $category;
    public ?string $category_id;
    public ?string $check_number;
    public array $counterparties;
    public string $date;
    public ?string $datetime;
    public bool $is_pending;
    public ?array $location;
    public ?string $logo_url;
    public ?string $merchant_entity_id;
    public ?string $merchant_name;
    public ?string $name;
    public ?string $original_description;
    public ?array $payment_meta;
    public ?string $payment_channel;
    public ?string $pending_transaction_id;
    public ?array $personal_finance_category;
    public ?string $personal_finance_category_icon_url;
    public ?string $transaction_code;
    public string $transaction_id;
    public ?string $transaction_type;
    public ?string $unofficial_currency_code;
    public ?string $website;
}