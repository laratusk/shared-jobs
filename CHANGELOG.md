# Changelog

All notable changes to `laratusk/shared-jobs` will be documented in this file.

## 1.0.0 - 2024-01-01

- Initial release
- Cross-application job dispatch via shared database
- SharedJob facade with dispatch, dispatchAndWait, and fake support
- SharedJobReceived event with respond() for synchronous-like communication
- SharedJobListener abstract base class with auto-filtering
- Role-based configuration (dispatcher, consumer, both)
- Full test suite with Pest
