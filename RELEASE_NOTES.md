# Release Notes

## 2.1.0

Planned backward-compatible PHP SDK enhancement release.

### Added

- `WeBirrClient::getBillByReference($billReference)` for
  `GET /einvoice/api/bill`.
- `WeBirrClient::getBillByPaymentCode($paymentCode)` for
  `GET /einvoice/api/bill`.
- `WeBirrClient::getBills($paymentStatus, $lastTimeStamp, $limit)` for
  `GET /einvoice/api/bills`.
- TestEnv smoke tests covering create, update, status, bill lookup, bill list,
  bulk payments, stats, delete cleanup, and value-level response assertions for
  generated bill references, payment code, merchant ID, pending status, amount,
  and customer fields.
- Example coverage for bill lookup and bill listing.

### Changed

- `WeBirrClient` now sets `Bill::$merchantID` from the client merchant ID before
  bill create/update requests are serialized.
- README and examples use `WEBIRR_TEST_ENV_MERCHANT_ID` and
  `WEBIRR_TEST_ENV_API_KEY`.
- README and examples no longer ask users to set `Bill::$merchantID` manually.
- Composer package version is `2.1.0`.
- Runtime Composer dependencies now use bounded current-major constraints.
- PHPUnit dev dependency constraints now require patched versions, and the lock
  file has no known Composer audit advisories.

### Compatibility

- Existing public methods remain available.
- `Bill::$merchantID` remains on the model for gateway wire compatibility, but
  callers should treat it as SDK-managed state.
- This is a minor release, not a major release.

### Verification

- `composer test`: 10 tests, 19 assertions.
- `composer test:testenv`: 9 live TestEnv tests, 62 assertions.

### Publishing

See `RELEASE.md` for the Packagist release procedure. The release requires a
Git commit, `v2.1.0` tag, push of `main` and the tag, and a clean Composer
install verification after Packagist updates.
