# Laravel Plaid handbook

Laravel Plaid 2.0 provides a Guzzle transport, service wrappers and selected typed data objects for Plaid integrations. It supports Laravel 12/13 and PHP 8.2+, subject to the framework's PHP requirement. It does not provide a banking ledger, tenant authorization, a credential vault, transaction reconciliation, a webhook receiver or a complete synchronization job.

This reference describes the implementation in this repository. A wrapper's presence is not a claim that every provider product, region or current response format has been verified against a live account. Review the provider contract and your enabled products before production adoption.

## Install and discover

```sh
composer config repositories.plaid vcs https://github.com/MrNewport/laravel-plaid
composer require mrnewport/laravel-plaid:^2.0
php artisan vendor:publish --tag=plaid-config
```

The VCS source is available when your package registry does not index the tag. Composer discovery registers `PlaidClient`, `Plaid`, the `plaid` container alias and the facade alias. No package migration, route, queue or scheduler is installed.

```dotenv
PLAID_CLIENT_ID=your-server-side-client-id
PLAID_SECRET=your-server-side-secret
PLAID_ENVIRONMENT=sandbox
PLAID_VERSION=2020-09-14
PLAID_LOGGING_ENABLED=false
```

Keep credentials and access tokens on the server. Browser Link tokens are a distinct provider concept; never substitute the client secret or access token into browser code.

## Configuration reference

| Key | Default | Actual use |
| --- | --- | --- |
| `client_id`, `secret` | Environment values | Credential headers |
| `environment` | `sandbox` | Select a configured base URL |
| `version` | `2020-09-14` | `Plaid-Version` header |
| `urls.sandbox` | `https://sandbox.plaid.com` | Sandbox origin |
| `urls.development` | `https://development.plaid.com` | Configured development origin |
| `urls.production` | `https://production.plaid.com` | Configured production origin |
| `http_options.timeout` | 30 seconds | Guzzle request timeout |
| `http_options.connect_timeout` | 10 seconds | Guzzle connection timeout |
| `http_options.retry_enabled` | `true` | Install retry middleware |
| `http_options.retry_post_endpoints` | Empty list | Explicit safe POST path allowlist |
| `http_options.retry_max_attempts` | 3 | Maximum retry count; initial attempt is additional |
| `http_options.retry_delay` | 1000 milliseconds | Base for exponential delay |
| `http_options.handler` | Unset | Optional injected Guzzle handler/stack |
| `logging.enabled` | `false` | Log method/path and response status |
| `logging.channel` | `stack` | Laravel log channel when enabled |

An unknown environment falls back to the configured sandbox URL. The package does not validate arbitrary application overrides to the base-URL map. Treat all transport configuration as trusted server configuration; do not copy it from a request.

`webhooks.secret`, `webhooks.tolerance` and some `defaults` values exist in configuration, but no webhook verification route or receiver is registered. `LinkTokenService::create()` merges `client_name`, language and country-code defaults; products and other options must be supplied deliberately by the caller.

## Resolve a client or service

```php
use MrNewport\LaravelPlaid\Plaid;
use MrNewport\LaravelPlaid\PlaidClient;

$plaid = app(Plaid::class);
$transactions = $plaid->transactions();
$client = app(PlaidClient::class);
```

```php
use MrNewport\LaravelPlaid\Facades\Plaid;

$page = Plaid::transactions()->sync($accessToken, $savedCursor);
```

`Plaid` lazily creates one wrapper of each requested service class per instance. `getClient()` exposes its `PlaidClient` for a supported endpoint not wrapped by a service.

## Transport contract

`PlaidClient` exposes `request($method, $endpoint, $data)`, `post`, `get`, `delete` and `patch`. Endpoint arguments must be relative API paths starting with one `/` and containing only letters, digits, underscores, hyphens and slashes. Absolute URLs, protocol-relative URLs, query strings and traversal dots are refused before the request.

```php
$response = $client->post('/accounts/get', ['access_token' => $accessToken]);
```

GET data becomes query parameters; data for other methods is JSON. Credential headers are `PLAID-CLIENT-ID` and `PLAID-SECRET`, alongside JSON content type and `Plaid-Version`. Redirect following is disabled.

HTTP 204 returns an empty array. Other successes must contain valid JSON that decodes to a PHP array; invalid JSON or a scalar response raises `PlaidRequestException`. This is not a streaming or bounded-binary download client. Large-response admission limits belong to the host transport/application.

Do not pass user-controlled methods, paths or arbitrary endpoint selection through this low-level API. Expose a host action that selects one trusted service operation after authorization.

## Link and token exchange recipe

```php
$link = $plaid->linkToken()->create([
    'user' => ['client_user_id' => $opaqueUserId],
    'products' => ['transactions'],
    'country_codes' => ['US'],
    'language' => 'en',
]);

// Return only the intended short-lived Link token to the authorized browser.
$linkToken = $link['link_token'];

// After the host verifies the returned Link flow and workspace ownership:
$exchange = $plaid->item()->publicTokenExchange($publicToken);
```

The host stores the resulting access token encrypted, associates it with the approved workspace and item, and applies its consent/retention policy. The package does not create these records or authenticate browser callbacks.

## Transaction synchronization recipe

```php
$page = $plaid->transactions()->sync(
    $accessToken,
    $savedCursor,
    count: 100,
    options: [],
);

foreach ($page['added'] as $transaction) {
    $id = $transaction->transaction_id;
    $attributes = $transaction->toArray();
    // Apply the application's authorized accounting/import policy.
}
```

The sync result has `added` and `modified` lists of `DTOs\Transaction`, raw `removed` records, `next_cursor`, `has_more` and `request_id`.

A host job should acquire its own connection/workspace lock, request bounded pages, apply additions/modifications/removals idempotently and commit each accepted cursor with the corresponding local import state. Continue only while `has_more` is true and the job's own budget remains. The package does not loop pages, persist cursors, suppress duplicate records or implement accounting classifications.

A returned provider transaction is an observation for the host's workflow. It does not by itself establish final settlement or a complete financial record.

## Account and DTO projections

`AccountsService::get()` maps `accounts` to `Account` DTOs. `getBalance()` maps them to `AccountBalance` DTOs. `AuthService` and selected investment/transaction methods create their declared DTOs; many wrappers return the provider array directly.

`BaseDTO` assigns only properties declared by its class and ignores unknown keys. `toArray()` omits null/uninitialized properties and converts nested DTO values. These classes are convenience projections, not complete schema validators. Reading a required typed property that the response omitted can fail. Validate provider responses and required business fields before committing domain changes.

Amounts exposed as PHP floats must not become the application's authoritative exact-money representation without an explicit host conversion/policy.

## Retry behavior

The retry middleware considers HTTP 429, 500, 502, 503 and 504 responses. It retries only:

- GET and HEAD requests.
- POST requests whose exact path appears in `retry_post_endpoints`.

The default POST allowlist is empty, even though many Plaid reads use POST. Opt in only operations that your provider contract and application policy treat as safe to retry.

```php
'http_options' => [
    'retry_enabled' => true,
    'retry_post_endpoints' => ['/transactions/sync', '/accounts/get'],
    'retry_max_attempts' => 2,
    'retry_delay' => 250,
],
```

The configured maximum is checked against the retry counter. A value of 2 permits the initial request plus up to two retries. Delay is the configured base multiplied by `2 ** retryNumber`. This implementation does not honor `Retry-After`, add jitter or retry connection exceptions through that decider.

Never blanket-enable retries for transfers, payment initiation or other mutations. Network uncertainty requires an operation-specific idempotency and recovery policy; a transport exception does not prove the provider performed no action.

## Errors and logging

| Failure | Exception |
| --- | --- |
| Invalid endpoint argument | `InvalidArgumentException` |
| No provider response / Guzzle network failure | `PlaidRequestException` |
| HTTP 400 | `PlaidRequestException` |
| HTTP 401 | `PlaidAuthenticationException` |
| HTTP 429 after permitted retries | `PlaidRateLimitException` |
| Other HTTP errors | `PlaidException` |
| Invalid success JSON | `PlaidRequestException` |

`PlaidException` exposes `getErrorCode()`, `getErrorType()`, `getDisplayMessage()` and `getRequestId()`. Its message can contain provider-supplied diagnostic text, and its previous exception can retain request details. Apply the host's redaction policy before logging exceptions or showing errors to a browser.

When package logging is enabled, its middleware records request method/path and response status, not payloads or credential headers. That does not disable Guzzle/Laravel telemetry elsewhere in the application. Avoid external request/response logging for access tokens and financial data.

## Fully intercepted local test

```php
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MrNewport\LaravelPlaid\PlaidClient;
use MrNewport\LaravelPlaid\Services\TransactionsService;

$body = [
    'added' => [[
        'transaction_id' => 'fictional-1',
        'account_id' => 'fictional-checking',
        'amount' => 48.50,
        'date' => '2026-11-06',
        'is_pending' => false,
        'name' => 'Northwind Supplies',
    ]],
    'modified' => [], 'removed' => [],
    'next_cursor' => 'fixture-next', 'has_more' => false,
    'request_id' => 'fixture-request',
];
$config = config('plaid');
$config['client_id'] = 'fictional';
$config['secret'] = 'fictional';
$config['logging']['enabled'] = false;
$config['http_options']['retry_enabled'] = false;
$config['http_options']['handler'] = HandlerStack::create(new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], json_encode($body)),
]));

$client = new PlaidClient($config);
$page = (new TransactionsService($client))->sync('fictional-access-token');
```

Every request here terminates in `MockHandler`; exhaustion fails rather than reaching a real server. This tests the package's request/DTO path without credentials. Standalone PHP callers can construct the same client with a complete trusted configuration array; methods that consult Laravel's `config()` helper, such as Link token defaults, still require the appropriate application context.

## Binary-response limitations

`AssetsService::reportPdfGet()` currently expects JSON containing a base64 `pdf` field and decodes that field. `StatementsService::download()` uses the same JSON-array client contract as other wrappers. Do not assume these methods support an arbitrary raw PDF response. Verify the exact provider response format and add a tested transport adapter before relying on a binary endpoint.

## Complete wrapper index

The method signatures and endpoint names below are taken from the current service source. They document what the wrappers send; they are not a promise that every provider product is enabled for your account.

### AccountsService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $accessToken, ?array $accountIds = null, array $options = []): array` | `/accounts/get` |
| `getBalance(string $accessToken, ?array $accountIds = null, array $options = []): array` | `/accounts/balance/get` |

### AssetsService

| Method | Fixed endpoint |
| --- | --- |
| `reportCreate(array $tokens, int $daysRequested, array $options = []): array` | `/asset_report/create` |
| `reportGet(string $assetReportToken, bool $includeInsights = false): array` | `/asset_report/get` |
| `reportPdfGet(string $assetReportToken): string` | `/asset_report/pdf/get` |
| `reportRemove(string $assetReportToken): array` | `/asset_report/remove` |
| `reportAuditCopyCreate(string $assetReportToken, string $auditorId): array` | `/asset_report/audit_copy/create` |
| `reportAuditCopyGet(string $auditCopyToken): array` | `/asset_report/audit_copy/get` |
| `reportAuditCopyRemove(string $auditCopyToken): array` | `/asset_report/audit_copy/remove` |
| `reportRefresh(string $assetReportToken, int $daysRequested, array $options = []): array` | `/asset_report/refresh` |
| `reportFilter(string $assetReportToken, array $accountIdsToInclude): array` | `/asset_report/filter` |

### AuthService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $accessToken, ?array $accountIds = null, array $options = []): array` | `/auth/get` |

### EmploymentService

| Method | Fixed endpoint |
| --- | --- |
| `verificationGet(string $accessToken): array` | `/employment/verification/get` |

### IdentityService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $accessToken, array $options = []): array` | `/identity/get` |
| `match(string $accessToken, array $options = []): array` | `/identity/match` |

### IdentityVerificationService

| Method | Fixed endpoint |
| --- | --- |
| `create(array $data): array` | `/identity_verification/create` |
| `get(string $identityVerificationId): array` | `/identity_verification/get` |
| `list( string $clientUserId, ?string $cursor = null, ?int $count = null ): array` | `/identity_verification/list` |
| `retryProcess( string $clientUserId, string $strategy, ?string $identityVerificationId = null, ?string $template = null, ?array $user = null ): array` | `/identity_verification/retry` |

### IncomeService

| Method | Fixed endpoint |
| --- | --- |
| `verificationCreate(string $clientUserId, string $webhook, array $options = []): array` | `/income/verification/create` |
| `verificationDocumentsDownload( string $clientUserId, ?string $accessToken = null, ?string $incomeVerificationId = null, ?string $documentId = null ): array` | `/income/verification/documents/download` |
| `verificationPaystubsGet( ?string $incomeVerificationId = null, ?string $accessToken = null ): array` | `/income/verification/paystubs/get` |
| `verificationTaxformsGet( ?string $incomeVerificationId = null, ?string $accessToken = null ): array` | `/income/verification/taxforms/get` |
| `verificationSummaryGet( ?string $incomeVerificationId = null, ?string $accessToken = null ): array` | `/income/verification/summary/get` |
| `verificationRefresh( ?string $incomeVerificationId = null, ?string $accessToken = null ): array` | `/income/verification/refresh` |

### InstitutionsService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $institutionId, array $countryCodes, array $options = []): array` | `/institutions/get_by_id` |
| `search( string $query, array $countryCodes, ?array $products = null, array $options = [] ): array` | `/institutions/search` |
| `getList( array $countryCodes, int $count = 100, int $offset = 0, array $options = [] ): array` | `/institutions/get` |

### InvestmentsService

| Method | Fixed endpoint |
| --- | --- |
| `holdingsGet(string $accessToken, array $options = []): array` | `/investments/holdings/get` |
| `transactionsGet( string $accessToken, string $startDate, string $endDate, array $options = [] ): array` | `/investments/transactions/get` |
| `refresh(string $accessToken): array` | `/investments/refresh` |

### ItemService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $accessToken): array` | `/item/get` |
| `remove(string $accessToken): array` | `/item/remove` |
| `publicTokenExchange(string $publicToken): array` | `/item/public_token/exchange` |
| `createPublicToken(string $accessToken): array` | `/item/public_token/create` |
| `accessTokenInvalidate(string $accessToken): array` | `/item/access_token/invalidate` |
| `webhook(string $accessToken, string $webhookUrl): array` | `/item/webhook/update` |
| `import(array $products, array $userAuth): array` | `/item/import` |

### LiabilitiesService

| Method | Fixed endpoint |
| --- | --- |
| `get(string $accessToken, array $options = []): array` | `/liabilities/get` |

### LinkTokenService

| Method | Fixed endpoint |
| --- | --- |
| `create(array $data): array` | `/link/token/create` |
| `get(string $linkToken): array` | `/link/token/get` |

### PaymentInitiationService

| Method | Fixed endpoint |
| --- | --- |
| `recipientCreate(array $data): array` | `/payment_initiation/recipient/create` |
| `recipientGet(string $recipientId): array` | `/payment_initiation/recipient/get` |
| `recipientList(): array` | `/payment_initiation/recipient/list` |
| `paymentCreate(array $data): array` | `/payment_initiation/payment/create` |
| `paymentGet(string $paymentId): array` | `/payment_initiation/payment/get` |
| `paymentList(array $options = []): array` | `/payment_initiation/payment/list` |
| `paymentReverse(string $paymentId, array $data = []): array` | `/payment_initiation/payment/reverse` |
| `consentCreate(array $data): array` | `/payment_initiation/consent/create` |
| `consentGet(string $consentId): array` | `/payment_initiation/consent/get` |
| `consentRevoke(string $consentId): array` | `/payment_initiation/consent/revoke` |
| `consentPaymentExecute(string $consentId, array $data): array` | `/payment_initiation/consent/payment/execute` |

### ProcessorService

| Method | Fixed endpoint |
| --- | --- |
| `tokenCreate(string $accessToken, string $accountId, string $processor): array` | `/processor/token/create` |
| `stripeTokenCreate(string $accessToken, string $accountId): array` | `/processor/stripe/bank_account_token/create` |
| `apexTokenCreate(string $accessToken, string $accountId): array` | Delegates to `tokenCreate()` |
| `dwollaTokenCreate(string $accessToken, string $accountId): array` | Delegates to `tokenCreate()` |
| `authGet(string $processorToken): array` | `/processor/auth/get` |
| `identityGet(string $processorToken): array` | `/processor/identity/get` |
| `balanceGet(string $processorToken, array $options = []): array` | `/processor/balance/get` |
| `accountGet(string $processorToken): array` | `/processor/account/get` |

### SandboxService

| Method | Fixed endpoint |
| --- | --- |
| `publicTokenCreate( string $institutionId, array $initialProducts, array $options = [] ): array` | `/sandbox/public_token/create` |
| `itemFireWebhook(string $accessToken, string $webhookCode): array` | `/sandbox/item/fire_webhook` |
| `itemResetLogin(string $accessToken): array` | `/sandbox/item/reset_login` |
| `itemSetVerificationStatus( string $accessToken, string $accountId, string $verificationStatus ): array` | `/sandbox/item/set_verification_status` |
| `processorTokenCreate(string $institutionId, array $options = []): array` | `/sandbox/processor_token/create` |
| `bankTransferSimulate( string $bankTransferId, string $eventType, ?string $failureReason = null ): array` | `/sandbox/bank_transfer/simulate` |
| `transferSimulate( string $transferId, string $eventType, ?string $failureReason = null ): array` | `/sandbox/transfer/simulate` |
| `transferSweepSimulate(string $testClockId): array` | `/sandbox/transfer/sweep/simulate` |
| `transferRefundSimulate(string $refundId, string $testClockId): array` | `/sandbox/transfer/refund/simulate` |
| `incomeVerificationPrecheckUpdate( string $incomeVerificationPrecheckId, ?string $completionStatus = null, ?array $completionResult = null ): array` | `/sandbox/income/verification/precheck` |

### StatementsService

| Method | Fixed endpoint |
| --- | --- |
| `list(string $accessToken, array $options = []): array` | `/statements/list` |
| `download(string $accessToken, string $statementId): array` | `/statements/download` |
| `refresh(string $accessToken, array $options = []): array` | `/statements/refresh` |

### TransactionsService

| Method | Fixed endpoint |
| --- | --- |
| `sync(string $accessToken, ?string $cursor = null, int $count = 100, array $options = []): array` | `/transactions/sync` |
| `get( string $accessToken, string $startDate, string $endDate, ?array $accountIds = null, int $count = 100, int $offset = 0, array $options = [] ): array` | `/transactions/get` |
| `refresh(string $accessToken): array` | `/transactions/refresh` |
| `recurring(string $accessToken, ?array $accountIds = null): array` | `/transactions/recurring/get` |
| `enrich(array $transactions, array $options = []): array` | `/transactions/enrich` |
| `categorize(string $query, ?array $options = null): array` | `/transactions/categorize` |

### TransferService

| Method | Fixed endpoint |
| --- | --- |
| `create(array $data): array` | `/transfer/create` |
| `get(string $transferId): array` | `/transfer/get` |
| `list( ?string $startDate = null, ?string $endDate = null, ?int $count = null, ?int $offset = null, ?string $originationAccountId = null ): array` | `/transfer/list` |
| `cancel(string $transferId): array` | `/transfer/cancel` |
| `eventList( ?string $startDate = null, ?string $endDate = null, ?string $transferId = null, ?string $accountId = null, ?string $transferType = null, ?array $eventTypes = null, ?string $sweepId = null, ?int $count = null, ?int $offset = null, ?string $originationAccountId = null ): array` | `/transfer/event/list` |
| `eventSync(string $afterId, ?int $count = null): array` | `/transfer/event/sync` |
| `sweepGet(string $sweepId): array` | `/transfer/sweep/get` |
| `sweepList( ?string $startDate = null, ?string $endDate = null, ?int $count = null, ?int $offset = null, ?string $originationAccountId = null ): array` | `/transfer/sweep/list` |
| `authorizationCreate(array $data): array` | `/transfer/authorization/create` |
| `originationAccountUpdate(string $originationAccountId, array $data): array` | `/transfer/origination_account/update` |
| `originationAccountGet(string $originationAccountId): array` | `/transfer/origination_account/get` |
| `originationAccountList( ?int $count = null, ?int $offset = null ): array` | `/transfer/origination_account/list` |
| `repaymentGet(string $repaymentId): array` | `/transfer/repayment/get` |
| `repaymentList( ?string $startDate = null, ?string $endDate = null, ?int $count = null, ?int $offset = null ): array` | `/transfer/repayment/list` |
| `repaymentReturnList(string $repaymentId): array` | `/transfer/repayment/return/list` |
| `intentCreate(array $data): array` | `/transfer/intent/create` |
| `intentGet(string $transferIntentId): array` | `/transfer/intent/get` |

## Application ownership and deployment

The host owns user consent, actor/workspace authorization, token encryption/rotation, provider product access, webhook signature verification, duplicate suppression, cursor transactions, accounting mappings and external-side-effect recovery. The package supplies no database tables or security policy for those concerns.

Bind separate trusted clients when different tenants/environments require different credentials. Long-running singleton clients capture the configuration used at construction. Keep sandbox data and live credentials in distinct application environments.

## Tests and contribution checks

```sh
composer install
composer test
```

The fixture suite covers client path admission, redirects, retries, error translation, logging, service payloads, DTO projections and provider/container wiring. See `tests/Unit/`, `tests/Feature/` and the CI workflow for exact current coverage. Local fakes are not live banking-provider acceptance. Add a contract test and representative failure fixture when extending a wrapper.

See the [feature inventory](FEATURES.md), [configuration](../config/plaid.php) and [transport source](../src/PlaidClient.php).
