# Changelog

## 2.0.0 — 2026-09-12

Laravel 12/13 with current development tooling. Fixed logging middleware to operate on asynchronous responses; logs now contain method/path/status only, never headers or payloads. Explicitly rejects external endpoint URLs and disables redirects. Automatic retries apply to safe GET/HEAD requests; retrying POST endpoints requires explicit per-path opt-in. Invalid response JSON produces a package exception.
