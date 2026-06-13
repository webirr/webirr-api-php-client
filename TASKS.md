# PHP SDK Task List

Status legend: `todo`, `in_progress`, `done`, `blocked`.

| ID | Status | Task |
| --- | --- | --- |
| PHP-SDK-001 | done | Keep release backward compatible and bump package version from `2.0.3` to `2.1.0`. |
| PHP-SDK-002 | done | Keep canonical RESTful gateway routes for create, update, delete, payment status, bulk payments, and stats. |
| PHP-SDK-003 | done | Add `getBillByReference($billReference)` using `GET /einvoice/api/bill`. |
| PHP-SDK-004 | done | Add `getBillByPaymentCode($paymentCode)` using `GET /einvoice/api/bill`. |
| PHP-SDK-005 | done | Add `getBills($paymentStatus, $lastTimeStamp, $limit)` using `GET /einvoice/api/bills`. |
| PHP-SDK-006 | done | Make `WeBirrClient` set `Bill::$merchantID` from the client merchant ID before bill create/update serialization. |
| PHP-SDK-007 | done | Remove manual `Bill::$merchantID` assignment from examples and README quick starts. |
| PHP-SDK-008 | done | Update examples to use `WEBIRR_TEST_ENV_MERCHANT_ID` and `WEBIRR_TEST_ENV_API_KEY`. |
| PHP-SDK-009 | done | Add example coverage for bill lookup and bill listing endpoints. |
| PHP-SDK-010 | done | Add Composer scripts for normal and live TestEnv test runs. |
| PHP-SDK-011 | done | Update TestEnv smoke tests to create one bill without setting `Bill::$merchantID`, update it, retrieve status, retrieve by reference, retrieve by payment code, list bills, poll payments, read stats, and delete cleanup with value-level assertions. |
| PHP-SDK-012 | done | Add release notes for the `2.1.0` package update. |
| PHP-SDK-013 | done | Run PHP/Composer validation, syntax checks, normal tests, and live TestEnv smoke tests locally. |
| PHP-SDK-014 | todo | Separate future task: build the SQLite bill synchronization example with local bill/payment state, bulk payment polling, retry metadata, and TestEnv verification. |
| PHP-SDK-015 | done | Bound Composer dependency ranges and refresh the lock file so the test dependency set has no known Composer audit advisories. |
| PHP-SDK-016 | done | Add `Bill::$customerPhone` to PHP bill payloads and verify it in TestEnv bill retrieval. |
| PHP-SDK-017 | done | Make `Bill::$customerPhone` optional with a backward-compatible empty-string default. |
| PHP-SDK-018 | done | Serialize empty `Bill::$extras` as an empty JSON object instead of a fake empty-key dictionary. |
| PHP-SDK-019 | done | Prefer `Payment::$paymentDate`, keep `Payment::$time` as a deprecated alias, and normalize both response shapes. |
| PHP-SDK-020 | done | Prepare PHP SDK patch release `2.1.1`. |
| PHP-SDK-021 | done | Prepare PHP SDK patch release `2.1.2` for timestamp cursor examples and docs. |
| PHP-SDK-022 | done | Prepare PHP SDK patch release `2.1.3` for README result-handling examples. |

## Verification

- `composer validate` passes with only the existing Packagist warning that this
  package declares an explicit `version` field.
- `composer audit` reports no known vulnerability advisories.
- `php -l` passes for every PHP file under `src`, `tests`, and `examples`.
- `composer test` passes: 16 tests, 27 assertions.
- `composer test:testenv` passes against WeBirr TestEnv merchant `0305`: 9 tests,
  65 assertions.

## Resume Note

The next enhancement task should be `PHP-SDK-014`: a separate SQLite bill sync
example. Keep it focused on local bill/payment state, TestEnv-only smoke
execution, bulk payment polling, retry metadata, and simple overnight/batch
synchronization behavior.
