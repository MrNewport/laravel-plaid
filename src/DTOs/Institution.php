<?php

namespace MrNewport\LaravelPlaid\DTOs;

class Institution extends BaseDTO
{
    public string $institution_id;
    public string $name;
    public array $products;
    public array $country_codes;
    public ?string $url;
    public ?string $primary_color;
    public ?string $logo;
    public array $routing_numbers;
    public bool $oauth;
    public array $status;
    public ?array $payment_initiation_metadata;
    public ?array $auth_metadata;
}