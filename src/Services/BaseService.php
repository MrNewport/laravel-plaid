<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\PlaidClient;

abstract class BaseService
{
    protected PlaidClient $client;

    public function __construct(PlaidClient $client)
    {
        $this->client = $client;
    }
}