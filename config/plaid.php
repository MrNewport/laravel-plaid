<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plaid API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Plaid API credentials. You can find these in your Plaid dashboard.
    |
    */
    'client_id' => env('PLAID_CLIENT_ID'),
    'secret' => env('PLAID_SECRET'),
    
    /*
    |--------------------------------------------------------------------------
    | Plaid Environment
    |--------------------------------------------------------------------------
    |
    | The Plaid environment to use. Options: sandbox, development, production
    |
    */
    'environment' => env('PLAID_ENVIRONMENT', 'sandbox'),
    
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The Plaid API version to use. Check Plaid docs for the latest version.
    |
    */
    'version' => env('PLAID_VERSION', '2020-09-14'),
    
    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    |
    | The base URLs for each Plaid environment.
    |
    */
    'urls' => [
        'sandbox' => 'https://sandbox.plaid.com',
        'development' => 'https://development.plaid.com',
        'production' => 'https://production.plaid.com',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Additional options to pass to the Guzzle HTTP client.
    |
    */
    'http_options' => [
        'timeout' => env('PLAID_TIMEOUT', 30),
        'connect_timeout' => env('PLAID_CONNECT_TIMEOUT', 10),
        'retry_enabled' => env('PLAID_RETRY_ENABLED', true),
        'retry_max_attempts' => env('PLAID_RETRY_MAX_ATTEMPTS', 3),
        'retry_delay' => env('PLAID_RETRY_DELAY', 1000), // milliseconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling Plaid webhooks.
    |
    */
    'webhooks' => [
        'secret' => env('PLAID_WEBHOOK_SECRET'),
        'tolerance' => env('PLAID_WEBHOOK_TOLERANCE', 300), // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default options for various Plaid products.
    |
    */
    'defaults' => [
        'country_codes' => ['US'],
        'language' => 'en',
        'products' => ['transactions'],
        'required_if_supported_products' => [],
        'optional_products' => [],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging for Plaid API requests and responses.
    |
    */
    'logging' => [
        'enabled' => env('PLAID_LOGGING_ENABLED', false),
        'channel' => env('PLAID_LOG_CHANNEL', 'stack'),
        'sensitive_fields' => [
            'access_token',
            'public_token',
            'processor_token',
            'account_number',
            'routing_number',
            'client_secret',
        ],
    ],
];