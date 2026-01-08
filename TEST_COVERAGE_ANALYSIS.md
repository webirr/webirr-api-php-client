# Test Coverage Analysis Report

## Executive Summary

The WeBirr PHP API Client has **limited test coverage** with significant gaps in both unit and integration testing. Currently, only **5 of 7 WeBirrClient methods** have any test coverage, and **none of the model classes** (Bill, Payment, Stat) have dedicated unit tests.

---

## Current Test Inventory

### Unit Tests (`tests/WeBirrTest.php`) - 5 Tests
| Test | Method Tested | Coverage Type |
|------|--------------|---------------|
| `test_CreateBill_should_get_error_from_WebService_on_invalid_api_key_TestEnv` | `createBill()` | Error path only |
| `test_CreateBill_should_get_error_from_WebService_on_invalid_api_key_ProdEnv` | `createBill()` | Error path only |
| `test_UpdateBill_should_get_error_from_WebService_on_invalid_api_key` | `updateBill()` | Error path only |
| `test_DeleteBill_should_get_error_from_WebService_on_invalid_api_key` | `deleteBill()` | Error path only |
| `test_GetPaymentStatus_should_get_error_from_WebService_on_invalid_api_key` | `getPaymentStatus()` | Error path only |

### Integration Tests (`tests/WeBirrTest_ext.php`) - 4 Tests
| Test | Method Tested | Coverage Type |
|------|--------------|---------------|
| `testCreateBill` | `createBill()` | Success path |
| `testUpdateBill` | `updateBill()` | Success path |
| `testGetPaymentStatus` | `getPaymentStatus()` | Success path |
| `testDeleteBill` | `deleteBill()` | Success path |

---

## Coverage Gaps Analysis

### 1. WeBirrClient Methods - CRITICAL GAPS

#### `getPayments()` - **NO TESTS** ❌
- **Location**: `src/WeBirrClient.php:127-135`
- **Risk Level**: HIGH
- **Impact**: Bulk payment polling is untested - this is a critical feature for production systems that need to track multiple payments
- **Recommended Tests**:
  - Unit test: Error response with invalid credentials
  - Integration test: Successful retrieval of payment list
  - Edge case: Empty result set handling
  - Edge case: Pagination with different `limit` values

#### `getStat()` - **NO TESTS** ❌
- **Location**: `src/WeBirrClient.php:145-153`
- **Risk Level**: MEDIUM
- **Impact**: Statistics retrieval is untested - affects reporting functionality
- **Recommended Tests**:
  - Unit test: Error response with invalid credentials
  - Integration test: Successful statistics retrieval
  - Edge case: Date range validation (invalid dates)

---

### 2. Model Classes - NO DEDICATED TESTS

#### `Bill.php` - **NO UNIT TESTS** ❌
- **Location**: `src/Bill.php`
- **Current Coverage**: Only indirectly tested through WeBirrClient tests
- **Missing Tests**:
  | Method/Property | Test Needed |
  |----------------|-------------|
  | `toArray()` | Verify correct array structure output |
  | Property initialization | Verify default values work correctly |
  | `extras` array | Test with various key-value pairs |

#### `Payment.php` - **NO UNIT TESTS** ❌
- **Location**: `src/Payment.php`
- **Missing Tests**:
  | Method | Test Needed |
  |--------|-------------|
  | `IsPaid()` | Return `true` when status=2, `false` otherwise |
  | `IsReversed()` | Return `true` when status=3, `false` otherwise |
  | `__construct($data)` | Test with array input, object input, null input |
  | `__construct($data)` | Test with missing properties in input |
  | `__construct($data)` | Test with extra properties in input (should be ignored) |

#### `Stat.php` - **NO UNIT TESTS** ❌
- **Location**: `src/Stat.php`
- **Missing Tests**:
  | Method | Test Needed |
  |--------|-------------|
  | `__construct($data)` | Test with array input, object input, null input |
  | `__construct($data)` | Test property mapping from API response structure |

---

### 3. Error Handling - INCOMPLETE COVERAGE

#### HTTP Non-200 Responses - **NOT TESTABLE** ⚠️
- **Location**: `src/WeBirrClient.php` (lines 52-55, 69-72, 89-92, 108-111, 131-134, 150-152)
- **Issue**: The code returns `['error' => 'http error ...']` for non-200 responses, but this path is never tested
- **Blocker**: Current tests hit real API and receive 200 responses with error payloads
- **Solution**: Mock the Guzzle HTTP client to test HTTP error scenarios

#### Network Failure Handling - **NOT TESTED** ❌
- **Issue**: No tests for `GuzzleHttp\Exception\*` exceptions
- **Risk**: Application may crash unexpectedly on network failures
- **Recommended Tests**:
  - Connection timeout handling
  - DNS resolution failure handling
  - SSL certificate errors

---

### 4. Test Infrastructure Issues

#### Missing `phpunit.xml` - **RECOMMENDED** ⚠️
- No PHPUnit configuration file exists
- **Impact**:
  - No consistent test execution settings
  - No code coverage reporting configured
  - Test suites not properly organized

#### No HTTP Client Mocking - **CRITICAL** ❌
- All tests hit real API endpoints
- **Problems**:
  - Tests are slow (network latency)
  - Tests are flaky (network/API availability)
  - Cannot test edge cases or error scenarios
  - Integration tests require real credentials

---

## Recommendations by Priority

### Priority 1: Critical (Should implement first)

1. **Add unit tests for `getPayments()` method**
   - This is a critical API method with zero test coverage

2. **Add unit tests for `getStat()` method**
   - No test coverage for statistics functionality

3. **Add unit tests for `Payment` model class**
   - `IsPaid()` and `IsReversed()` methods are business-critical
   - Constructor needs validation testing

### Priority 2: High (Should implement soon)

4. **Implement HTTP client mocking**
   - Use a mocking library (e.g., `php-http/mock-client` or Guzzle's MockHandler)
   - Enable testing of HTTP error paths
   - Make tests faster and more reliable

5. **Add `phpunit.xml` configuration**
   - Configure test suites (unit vs integration)
   - Enable code coverage reporting
   - Set up proper autoloading for tests

6. **Add unit tests for `Bill` model class**
   - Test `toArray()` output structure
   - Test property initialization

### Priority 3: Medium (Nice to have)

7. **Add unit tests for `Stat` model class**
   - Test constructor with various inputs

8. **Add network failure tests**
   - Test exception handling for timeouts, DNS failures, etc.

9. **Add input validation tests**
   - Test with empty strings, null values, special characters

---

## Proposed Test File Structure

```
tests/
├── Unit/
│   ├── BillTest.php           # NEW: Bill model unit tests
│   ├── PaymentTest.php        # NEW: Payment model unit tests
│   ├── StatTest.php           # NEW: Stat model unit tests
│   └── WeBirrClientTest.php   # REFACTOR: Move from WeBirrTest.php, add mocking
├── Integration/
│   └── WeBirrClientIntegrationTest.php  # REFACTOR: Move from WeBirrTest_ext.php
└── bootstrap.php              # NEW: Test bootstrap file
```

---

## Sample Test Implementations

### Payment Model Tests (Priority 1)

```php
<?php
namespace WeBirr\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeBirr\Payment;

class PaymentTest extends TestCase
{
    public function test_IsPaid_returns_true_when_status_is_2()
    {
        $payment = new Payment(['status' => 2]);
        $this->assertTrue($payment->IsPaid());
    }

    public function test_IsPaid_returns_false_when_status_is_not_2()
    {
        $payment = new Payment(['status' => 1]);
        $this->assertFalse($payment->IsPaid());

        $payment = new Payment(['status' => 3]);
        $this->assertFalse($payment->IsPaid());
    }

    public function test_IsReversed_returns_true_when_status_is_3()
    {
        $payment = new Payment(['status' => 3]);
        $this->assertTrue($payment->IsReversed());
    }

    public function test_IsReversed_returns_false_when_status_is_not_3()
    {
        $payment = new Payment(['status' => 1]);
        $this->assertFalse($payment->IsReversed());

        $payment = new Payment(['status' => 2]);
        $this->assertFalse($payment->IsReversed());
    }

    public function test_constructor_with_array_populates_properties()
    {
        $data = [
            'status' => 2,
            'id' => '12345',
            'bankID' => 'BANK001',
            'amount' => '100.50',
            'wbcCode' => 'WBC123',
        ];

        $payment = new Payment($data);

        $this->assertEquals(2, $payment->status);
        $this->assertEquals('12345', $payment->id);
        $this->assertEquals('BANK001', $payment->bankID);
        $this->assertEquals('100.50', $payment->amount);
        $this->assertEquals('WBC123', $payment->wbcCode);
    }

    public function test_constructor_with_object_populates_properties()
    {
        $data = (object)[
            'status' => 2,
            'id' => '12345',
        ];

        $payment = new Payment($data);

        $this->assertEquals(2, $payment->status);
        $this->assertEquals('12345', $payment->id);
    }

    public function test_constructor_ignores_unknown_properties()
    {
        $data = [
            'status' => 2,
            'unknownProperty' => 'should be ignored',
        ];

        $payment = new Payment($data);

        $this->assertEquals(2, $payment->status);
        $this->assertFalse(property_exists($payment, 'unknownProperty'));
    }
}
```

### Bill Model Tests (Priority 2)

```php
<?php
namespace WeBirr\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WeBirr\Bill;

class BillTest extends TestCase
{
    public function test_toArray_returns_correct_structure()
    {
        $bill = new Bill();
        $bill->amount = '100.50';
        $bill->customerCode = 'CUST001';
        $bill->customerName = 'John Doe';
        $bill->time = '2024-01-15 10:30';
        $bill->description = 'Test invoice';
        $bill->billReference = 'REF001';
        $bill->merchantID = 'MERCH001';
        $bill->extras = ['key1' => 'value1'];

        $result = $bill->toArray();

        $this->assertIsArray($result);
        $this->assertEquals('100.50', $result['amount']);
        $this->assertEquals('CUST001', $result['customerCode']);
        $this->assertEquals('John Doe', $result['customerName']);
        $this->assertEquals('2024-01-15 10:30', $result['time']);
        $this->assertEquals('Test invoice', $result['description']);
        $this->assertEquals('REF001', $result['billReference']);
        $this->assertEquals('MERCH001', $result['merchantID']);
        $this->assertEquals(['key1' => 'value1'], $result['extras']);
    }

    public function test_toArray_includes_all_required_keys()
    {
        $bill = new Bill();
        $bill->amount = '';
        $bill->customerCode = '';
        $bill->customerName = '';
        $bill->time = '';
        $bill->description = '';
        $bill->billReference = '';
        $bill->merchantID = '';

        $result = $bill->toArray();

        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('customerCode', $result);
        $this->assertArrayHasKey('customerName', $result);
        $this->assertArrayHasKey('time', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('billReference', $result);
        $this->assertArrayHasKey('merchantID', $result);
        $this->assertArrayHasKey('extras', $result);
    }
}
```

### getPayments Unit Test (Priority 1)

```php
// Add to WeBirrTest.php
public function test_GetPayments_should_get_error_from_WebService_on_invalid_api_key()
{
    $api = new WeBirrClient('x', 'x', true);
    $res = $api->getPayments('', 10);

    $this->assertTrue(strlen($res->errorCode) > 0);
}

// Add to WeBirrTest_ext.php
public function testGetPayments()
{
    $res = self::$api->getPayments('', 10);
    $this->assertEmpty($res->error, "GetPayments error: {$res->error}");
    $this->assertIsArray($res->res);
}
```

---

## Coverage Metrics Summary

| Component | Methods/Features | Tested | Coverage |
|-----------|-----------------|--------|----------|
| WeBirrClient | 7 methods | 5 | **71%** |
| Bill | 1 method + 8 properties | 0 | **0%** |
| Payment | 3 methods + 13 properties | 0 | **0%** |
| Stat | 1 method + 6 properties | 0 | **0%** |
| **Overall Estimated** | | | **~35%** |

---

## Next Steps

1. Create `phpunit.xml` with proper test suite configuration
2. Implement the missing unit tests for `getPayments()` and `getStat()`
3. Create dedicated test files for model classes
4. Consider adding Guzzle MockHandler for HTTP mocking
5. Set up code coverage reporting (e.g., with PHPUnit + Xdebug)
