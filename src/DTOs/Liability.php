<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Liability extends BaseDTO
{
    public string $account_id;
    public ?array $aprs;
    public ?string $last_payment_date;
    public ?float $last_payment_amount;
    public ?string $last_statement_issue_date;
    public ?float $last_statement_balance;
    public ?float $minimum_payment_amount;
    public ?string $next_payment_due_date;
    public ?string $origination_date;
    public ?float $principal;
}