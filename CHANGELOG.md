# Changelog

All notable changes to `laratusk/shared-jobs` will be documented in this file.

## 1.0.0 - 2026-03-26

- Added Laravel 13 support (`illuminate/*` `^13.0`)
- Added Laravel 13 to the CI test matrix with Testbench 11, excluding PHP 8.2 (Laravel 13 requires PHP 8.3+)
- Allowed Pest 4 and pest-plugin-laravel 4 for Laravel 13 compatibility
- Updated README requirements to Laravel 11, 12, or 13

## 0.1.0 - 2026-03-11

- Initial release
- Cross-application job dispatch via a shared database queue
- SharedJob facade with dispatch(), dispatchAndWait() and fake() with dispatch assertions
- SharedJobReceived event with respond() for synchronous-like request/response
- SharedJobListener abstract base class with automatic job name filtering
- Role-based configuration via the Role enum (dispatcher, consumer, both)
- Migrations for the shared_jobs and shared_job_results tables
- Full test suite with Pest, plus CI code quality checks (Pint, PHPStan/Larastan, Rector)
