# Laravel Plaid

Read the [complete handbook](docs/HANDBOOK.md) for installation, contracts, configuration, failures and application recipes. The [feature inventory](docs/FEATURES.md) maps implemented behavior to its boundaries.

Current release: **2.0.0**. Supported installations: Laravel 12 (PHP 8.2+) and Laravel 13 (PHP 8.3+). CI verifies supported PHP/Laravel combinations. Earlier Laravel versions should remain on the previous major release.

Laravel 12/13 with current development tooling. Fixed logging middleware to operate on asynchronous responses; logs now contain method/path/status only, never headers or payloads. Explicitly rejects external endpoint URLs and disables redirects. Automatic retries apply to safe GET/HEAD requests; retrying POST endpoints requires explicit per-path opt-in. Invalid response JSON produces a package exception.

```sh
composer require mrnewport/laravel-plaid:^2.0
composer test # from the package checkout; tests use isolated fixtures
```

GitHub source and tags are published first. Until the release is indexed on Packagist, add this repository as a Composer VCS repository. Never install test dependencies in your production application's require section.

## Upgrade from 1.x

HTTP middleware now runs with supplied Guzzle handlers as well as the default handler. Request/response payload logging is removed. POST retries are disabled unless the exact endpoint is listed in `http_options.retry_post_endpoints`; only opt in when the operation is idempotent. This package supplies API services, not webhook signature verification or a ready-made webhook route. Never treat the legacy `webhooks.secret` configuration value as a verified webhook implementation. Tests use mocked HTTP responses; no bank access or money transfers are made.

## Existing API reference

# Laravel Plaid Package

Laravel services and DTOs for the Plaid endpoints listed below.

[![Latest Version](https://img.shields.io/packagist/v/mrnewport/laravel-plaid.svg?style=flat-square)](https://packagist.org/packages/mrnewport/laravel-plaid)
[![MIT License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Laravel 12.x](https://img.shields.io/badge/Laravel-12.x-red.svg?style=flat-square)](https://laravel.com)

## Features

- 🚀 **Complete API Coverage**: Every Plaid endpoint is implemented
- 🔒 **Type-Safe**: Full PHP type hints and DTOs for all responses
- 🛡️ **Explicit Transport Policies**: Safe retry admission, typed errors, and metadata logging
- 🧪 **Fully Tested**: 100% test coverage with Pest
- 📝 **Well Documented**: Clear examples and comprehensive documentation
- ⚡ **Laravel Native**: Service provider, facades, and config publishing
- 🔄 **Auto Retry**: Configurable retry logic for failed requests
- 📊 **Request Logging**: Optional method/path/status logging without payloads or headers

## Requirements

- PHP 8.2+ (Laravel 13 requires PHP 8.3+)
- Laravel 12.x or 13.x

## Installation

Install the package via Composer:

```bash
composer require mrnewport/laravel-plaid
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="MrNewport\LaravelPlaid\PlaidServiceProvider" --tag="plaid-config"
```

Add your Plaid credentials to your `.env` file:

```env
PLAID_CLIENT_ID=your_client_id
PLAID_SECRET=your_secret_key
PLAID_ENVIRONMENT=sandbox # sandbox, development, or production
PLAID_VERSION=2020-09-14
```

### Optional Configuration

```env
# HTTP Client Options
PLAID_TIMEOUT=30
PLAID_CONNECT_TIMEOUT=10
PLAID_RETRY_ENABLED=true
PLAID_RETRY_MAX_ATTEMPTS=3
PLAID_RETRY_DELAY=1000

# Webhook Configuration
PLAID_WEBHOOK_SECRET=your_webhook_secret
PLAID_WEBHOOK_TOLERANCE=300

# Logging
PLAID_LOGGING_ENABLED=false
PLAID_LOG_CHANNEL=stack
```

## Usage

### Basic Usage

```php
use MrNewport\LaravelPlaid\Facades\Plaid;

// Create a link token for Plaid Link
$response = Plaid::linkToken()->create([
    'user' => [
        'client_user_id' => 'user-' . $user->id,
    ],
    'products' => ['auth', 'transactions'],
    'client_name' => 'Your App Name',
    'country_codes' => ['US'],
    'language' => 'en',
    'webhook' => 'https://yourapp.com/webhooks/plaid',
]);

$linkToken = $response['link_token'];
```

### Exchange Public Token

```php
// After user completes Plaid Link
$response = Plaid::item()->publicTokenExchange($publicToken);
$accessToken = $response['access_token'];
$itemId = $response['item_id'];

// Store these securely in your database
$user->plaid_access_token = encrypt($accessToken);
$user->plaid_item_id = $itemId;
$user->save();
```

### Get Accounts

```php
$response = Plaid::accounts()->get($accessToken);

foreach ($response['accounts'] as $account) {
    echo $account->name . ': $' . $account->balances['current'] . PHP_EOL;
}
```

### Get Transactions

```php
// Using the new Transactions Sync endpoint (recommended)
$cursor = $user->plaid_cursor; // Store cursor for incremental updates
$hasMore = true;

while ($hasMore) {
    $response = Plaid::transactions()->sync($accessToken, $cursor);
    
    foreach ($response['added'] as $transaction) {
        // Process new transactions
        Transaction::create([
            'account_id' => $transaction->account_id,
            'amount' => $transaction->amount,
            'name' => $transaction->name,
            'date' => $transaction->date,
            'category' => $transaction->category,
        ]);
    }
    
    foreach ($response['modified'] as $transaction) {
        // Update existing transactions
    }
    
    foreach ($response['removed'] as $removed) {
        // Delete removed transactions
    }
    
    $cursor = $response['next_cursor'];
    $hasMore = $response['has_more'];
}

// Save cursor for next sync
$user->plaid_cursor = $cursor;
$user->save();
```

### Get Auth Information

```php
$response = Plaid::auth()->get($accessToken);

foreach ($response['accounts'] as $account) {
    $accountNumbers = $response['numbers']->ach;
    foreach ($accountNumbers as $ach) {
        if ($ach['account_id'] === $account['account_id']) {
            echo "Routing: {$ach['routing']}, Account: {$ach['account']}" . PHP_EOL;
        }
    }
}
```

### Create ACH Transfer

```php
// First, create a transfer authorization
$authResponse = Plaid::transfer()->authorizationCreate([
    'access_token' => $accessToken,
    'account_id' => $accountId,
    'type' => 'debit',
    'network' => 'ach',
    'amount' => '100.00',
    'ach_class' => 'ppd',
    'user' => [
        'legal_name' => 'John Doe',
        'email_address' => 'john@example.com',
    ],
]);

// Then create the transfer
if ($authResponse['authorization']['decision'] === 'approved') {
    $transferResponse = Plaid::transfer()->create([
        'access_token' => $accessToken,
        'account_id' => $accountId,
        'authorization_id' => $authResponse['authorization']['id'],
        'description' => 'Payment for order #1234',
    ]);
    
    $transferId = $transferResponse['transfer']['id'];
}
```

### Identity Verification

```php
$response = Plaid::identityVerification()->create([
    'template_id' => 'idvtmp_xxxxx',
    'gave_consent' => true,
    'user' => [
        'client_user_id' => 'user-' . $user->id,
        'email_address' => $user->email,
        'phone_number' => $user->phone,
        'date_of_birth' => $user->date_of_birth,
        'name' => [
            'given_name' => $user->first_name,
            'family_name' => $user->last_name,
        ],
        'address' => [
            'street' => $user->street_address,
            'city' => $user->city,
            'region' => $user->state,
            'postal_code' => $user->zip,
            'country' => 'US',
        ],
    ],
]);
```

### Investment Data

```php
// Get investment holdings
$holdings = Plaid::investments()->holdingsGet($accessToken);

foreach ($holdings['holdings'] as $holding) {
    echo "{$holding['quantity']} shares of {$holding['security_id']}" . PHP_EOL;
}

// Get investment transactions
$transactions = Plaid::investments()->transactionsGet(
    $accessToken,
    '2024-01-01',
    '2024-12-31'
);
```

### Create Processor Token

```php
// For Stripe
$response = Plaid::processor()->stripeTokenCreate($accessToken, $accountId);
$stripeToken = $response['stripe_bank_account_token'];

// For Dwolla
$response = Plaid::processor()->dwollaTokenCreate($accessToken, $accountId);
$processorToken = $response['processor_token'];

// For other processors
$response = Plaid::processor()->tokenCreate($accessToken, $accountId, 'achq');
$processorToken = $response['processor_token'];
```

## Available Services

### Core Services

- **Accounts**: `Plaid::accounts()` - Account information and balances
- **Auth**: `Plaid::auth()` - Account and routing numbers
- **Transactions**: `Plaid::transactions()` - Transaction data and categorization
- **Identity**: `Plaid::identity()` - Account holder information
- **Balance**: `Plaid::accounts()->getBalance()` - Real-time balance

### Wealth & Investments

- **Investments**: `Plaid::investments()` - Investment holdings and transactions
- **Liabilities**: `Plaid::liabilities()` - Loan and credit card data

### Income & Employment

- **Income**: `Plaid::income()` - Income verification
- **Employment**: `Plaid::employment()` - Employment verification
- **Assets**: `Plaid::assets()` - Asset reports for lending

### Payments & Transfers

- **Transfer**: `Plaid::transfer()` - ACH transfers and payments
- **Payment Initiation**: `Plaid::paymentInitiation()` - UK/EU payments

### Risk & Compliance

- **Identity Verification**: `Plaid::identityVerification()` - KYC/AML
- **Monitor**: Built into relevant services
- **Beacon**: Built into relevant services

### Other Services

- **Link Token**: `Plaid::linkToken()` - Create and manage Link tokens
- **Item**: `Plaid::item()` - Manage Items (connections)
- **Institutions**: `Plaid::institutions()` - Institution information
- **Processor**: `Plaid::processor()` - Processor tokens
- **Statements**: `Plaid::statements()` - PDF statements
- **Sandbox**: `Plaid::sandbox()` - Testing utilities

## Error Handling

The package provides specific exception types for different error scenarios:

```php
use MrNewport\LaravelPlaid\Exceptions\PlaidException;
use MrNewport\LaravelPlaid\Exceptions\PlaidAuthenticationException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRateLimitException;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;

try {
    $accounts = Plaid::accounts()->get($accessToken);
} catch (PlaidAuthenticationException $e) {
    // Invalid API keys
    Log::error('Plaid authentication failed: ' . $e->getMessage());
} catch (PlaidRateLimitException $e) {
    // Rate limit exceeded
    Log::warning('Plaid rate limit hit: ' . $e->getMessage());
} catch (PlaidRequestException $e) {
    // Invalid request (400 errors)
    Log::error('Invalid Plaid request: ' . $e->getMessage());
    Log::error('Error code: ' . $e->getErrorCode());
    Log::error('Error type: ' . $e->getErrorType());
} catch (PlaidException $e) {
    // Other Plaid errors
    Log::error('Plaid error: ' . $e->getMessage());
}
```

## Webhooks

This package does not implement Plaid webhook signature verification or register a receiver route. The `webhooks.secret` and `webhooks.tolerance` keys are reserved configuration, not an authentication mechanism.

The host must authenticate the provider delivery using a separately reviewed verifier, authorize the item/workspace mapping, durably deduplicate the event and enqueue the appropriate sync job. Only then should application code inspect `webhook_type` and `webhook_code`. Do not expose a receiver that trusts decoded JSON merely because those fields look familiar.

See the [handbook](docs/HANDBOOK.md#application-ownership-and-deployment) for the complete application boundary.

## Logging

Enable method/path/status metadata logging for debugging:

```env
PLAID_LOGGING_ENABLED=true
PLAID_LOG_CHANNEL=plaid
```

Configure the log channel in `config/logging.php`:

```php
'channels' => [
    'plaid' => [
        'driver' => 'daily',
        'path' => storage_path('logs/plaid.log'),
        'level' => 'debug',
        'days' => 7,
    ],
],
```

Only method, path, and response status are logged; payloads and headers are omitted.

## Testing

The package includes comprehensive test coverage using Pest:

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Unit/Services/TransactionsServiceTest.php
```

### Mocking in Tests

```php
use MrNewport\LaravelPlaid\Facades\Plaid;

// In your test
Plaid::shouldReceive('accounts->get')
    ->once()
    ->with($accessToken)
    ->andReturn([
        'accounts' => [
            ['account_id' => 'test123', 'name' => 'Checking'],
        ],
    ]);
```

## Sandbox Testing

Use the sandbox environment for testing:

```php
// Create sandbox public token
$publicToken = Plaid::sandbox()->publicTokenCreate(
    'ins_109508',
    ['auth', 'transactions']
);

// Fire a webhook in sandbox
Plaid::sandbox()->itemFireWebhook($accessToken, 'DEFAULT_UPDATE');

// Simulate transfer events
Plaid::sandbox()->transferSimulate($transferId, 'posted');
```

## Advanced Usage

### Custom HTTP Client Options

```php
// config/plaid.php
'http_options' => [
    'timeout' => 60,
    'connect_timeout' => 10,
    'retry_enabled' => true,
    'retry_max_attempts' => 5,
    'retry_delay' => 2000,
    'proxy' => 'tcp://localhost:8080',
],
```

### Using Without Facade

```php
use MrNewport\LaravelPlaid\Plaid;
use MrNewport\LaravelPlaid\PlaidClient;

// Inject via constructor
public function __construct(private Plaid $plaid)
{
}

// Or resolve from container
$plaid = app(Plaid::class);
$accounts = $plaid->accounts()->get($accessToken);
```

### Direct Client Access

```php
$client = Plaid::getClient();
$response = $client->post('/custom/endpoint', ['data' => 'value']);
```

## Troubleshooting

### Common Issues

1. **SSL Certificate Issues**
   ```bash
   # Download latest CA bundle
   curl -o /path/to/cacert.pem https://curl.se/ca/cacert.pem
   ```
   
   Configure in your `.env`:
   ```env
   CURL_CA_BUNDLE=/path/to/cacert.pem
   ```

2. **Rate Limiting**
   Safe GET/HEAD requests can retry rate-limit errors. POST retries require explicit per-endpoint opt-in. Adjust retry settings:
   ```env
   PLAID_RETRY_MAX_ATTEMPTS=5
   PLAID_RETRY_DELAY=5000
   ```

3. **Timeout Issues**
   Increase timeout for large requests:
   ```env
   PLAID_TIMEOUT=120
   PLAID_CONNECT_TIMEOUT=30
   ```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email admin@matthewnewport.com instead of using the issue tracker.

## Credits

- [MrNewport](https://github.com/mrnewport)
- [All Contributors](https://github.com/MrNewport/laravel-plaid/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

For support, please email admin@matthewnewport.com or create an issue on GitHub.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

---

Built with ❤️ for the Laravel community
