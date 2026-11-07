# Plaid feature inventory

| Capability | Implemented entry point | Qualification |
| --- | --- | --- |
| Laravel container/facade integration | `PlaidServiceProvider`, `Facades\Plaid` | No public routes or migrations |
| Trusted environment/base-URL configuration | `PlaidClient` | Unknown environment falls back to configured sandbox |
| Relative endpoint admission | `request`, `get`, `post`, `delete`, `patch` | No arbitrary absolute endpoint argument |
| Credential/version headers and disabled redirects | `PlaidClient::createHttpClient()` | Configuration must remain server controlled |
| JSON response decoding | `PlaidClient::request()` | Not a generic binary streaming client |
| Explicit safe retry policy | `retry_post_endpoints` and GET/HEAD | No default POST retries or mutation idempotency |
| Typed HTTP error classes | `Exceptions/*` | Provider/previous-exception data still needs host redaction |
| Metadata-only optional package logging | `loggingMiddleware()` | Does not disable other application telemetry |
| Injected handler/stack | `http_options.handler` | Enables complete local fixture interception |
| Link and item lifecycle wrappers | `LinkTokenService`, `ItemService` | Consent, browser flow and token vault are host owned |
| Account/auth/transaction DTO paths | `AccountsService`, `AuthService`, `TransactionsService`, `DTOs/*` | DTOs are projections, not full response validators |
| Cursor-based transaction page | `TransactionsService::sync()` | Host loops, deduplicates and commits cursors |
| Exact transaction sync facts | `TransactionsService::syncExact()` and `ExactTransaction` | Bounded raw numeric lexemes, currency, pending links, removals and cursor; host owns persisted Item/cursor state |
| Plaid webhook signature verification | `Plaid::webhookVerification()` | Bounded ES256 JWT/JWK and exact raw-body check; host owns receiver, Item authority, replay inbox and work queue |
| Assets, institutions, identity, income and employment wrappers | Corresponding service classes | Verify enabled products and provider contracts |
| Investments and liabilities wrappers | Corresponding service classes | No accounting ledger or reconciliation policy |
| Transfers and payment-initiation wrappers | Corresponding service classes | Financial authorization/idempotency/recovery remain host owned |
| Processor and sandbox wrappers | Corresponding service classes | Sandbox simulation is not live-provider verification |
| Statement and asset PDF-named methods | `StatementsService`, `AssetsService` | Current implementation expects JSON; see binary limitations |

This proposed source adds a verifier service but registers no webhook receiver, automatic scheduler or sync database, and makes no claim of exhaustive current Plaid product coverage. The [handbook](HANDBOOK.md) lists every wrapper signature and its actual fixed endpoint.
