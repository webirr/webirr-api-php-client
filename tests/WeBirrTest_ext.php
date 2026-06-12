<?php

namespace WeBirr\Tests;

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use WeBirr\Bill;
use WeBirr\WeBirrClient;

class WeBirrTest_ext extends TestCase
{
    private const CREATED_AMOUNT = '270.90';
    private const UPDATED_AMOUNT = '278.00';
    private const CUSTOMER_CODE = 'sdk-test-customer';
    private const CREATED_CUSTOMER_NAME = 'SDK Test Customer';
    private const UPDATED_CUSTOMER_NAME = 'SDK Test Customer Updated';
    private const DESCRIPTION = 'SDK Test Bill';

    private static $paymentCode = null;
    private static $billReference = null;
    private static $billUpdateTimeStamp = null;
    private static $merchantId = null;
    private static $api = null;
    private static $deleted = false;

    public static function setUpBeforeClass(): void
    {
        self::$billReference = 'php/test/' . self::uuidV4();
        $apiKey = getenv('WEBIRR_TEST_ENV_API_KEY') ?: '';
        self::$merchantId = getenv('WEBIRR_TEST_ENV_MERCHANT_ID') ?: '';

        if (empty($apiKey) || empty(self::$merchantId)) {
            self::fail(
                "Environment variables WEBIRR_TEST_ENV_API_KEY and WEBIRR_TEST_ENV_MERCHANT_ID must be set for TestEnv smoke tests."
            );
        }

        self::$api = new WeBirrClient(self::$merchantId, $apiKey, true);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$api && self::$paymentCode && !self::$deleted) {
            self::$api->deleteBill(self::$paymentCode);
        }
    }

    public function testCreateBillWithoutMerchantId()
    {
        $bill = $this->sampleBill();
        $res = self::$api->createBill($bill);

        $this->assertNoApiError($res, 'CreateBill');
        $this->assertSame(self::$merchantId, $bill->merchantID);
        self::$paymentCode = $res->res;
        $this->assertNotEmpty(self::$paymentCode, "Payment code should not be empty");
        $this->assertMatchesRegularExpression('/^\d{3}\s\d{3}\s\d{3}$/', self::$paymentCode);
    }

    /**
     * @depends testCreateBillWithoutMerchantId
     */
    public function testUpdateBillWithoutMerchantId()
    {
        $bill = $this->sampleBill();
        $bill->amount = self::UPDATED_AMOUNT;
        $bill->customerName = self::UPDATED_CUSTOMER_NAME;
        $res = self::$api->updateBill($bill);

        $this->assertNoApiError($res, 'UpdateBill');
        $this->assertSame(self::$merchantId, $bill->merchantID);
        $this->assertSame('ok', strtolower((string)$res->res));
    }

    /**
     * @depends testCreateBillWithoutMerchantId
     */
    public function testGetPaymentStatus()
    {
        $res = self::$api->getPaymentStatus(self::$paymentCode);

        $this->assertNoApiError($res, 'GetPaymentStatus');
        $this->assertTrue(isset($res->res->status), 'Payment status should include status.');
        $this->assertSame(0, $res->res->status, 'New TestEnv bill should be pending payment.');
        $this->assertNull($res->res->data, 'Pending payment should not include payment detail data.');
    }

    /**
     * @depends testCreateBillWithoutMerchantId
     */
    public function testGetBillByReference()
    {
        $res = self::$api->getBillByReference(self::$billReference);

        $this->assertNoApiError($res, 'GetBillByReference');
        $this->assertNotEmpty($res->res, 'Bill lookup by reference should return bill data.');
        $this->assertBillMatchesExpected($res->res);
        self::$billUpdateTimeStamp = $res->res->updateTimeStamp;
    }

    /**
     * @depends testCreateBillWithoutMerchantId
     */
    public function testGetBillByPaymentCode()
    {
        $res = self::$api->getBillByPaymentCode(self::$paymentCode);

        $this->assertNoApiError($res, 'GetBillByPaymentCode');
        $this->assertNotEmpty($res->res, 'Bill lookup by payment code should return bill data.');
        $this->assertBillMatchesExpected($res->res);
    }

    /**
     * @depends testGetBillByReference
     */
    public function testGetBills()
    {
        $cursor = $this->cursorBefore(self::$billUpdateTimeStamp);
        $res = self::$api->getBills(0, $cursor, 100);

        $this->assertNoApiError($res, 'GetBills');
        $this->assertIsArray($res->res, 'Bill list should return an array.');
        $bill = $this->findBillByReference($res->res, self::$billReference);
        $this->assertNotNull($bill, 'Bill list should include the bill created by this test run.');
        $this->assertBillMatchesExpected($bill);
    }

    public function testGetPayments()
    {
        $res = self::$api->getPayments('', 10);

        $this->assertNoApiError($res, 'GetPayments');
        $this->assertIsArray($res->res, 'Payment list should return an array.');
    }

    public function testGetStat()
    {
        $res = self::$api->getStat('2025-01-01', '2030-01-31');

        $this->assertNoApiError($res, 'GetStat');
        $this->assertNotEmpty($res->res, 'Stats should return a result object.');
    }

    /**
     * @depends testGetPaymentStatus
     * @depends testGetBillByReference
     * @depends testGetBillByPaymentCode
     * @depends testGetBills
     */
    public function testDeleteBill()
    {
        $res = self::$api->deleteBill(self::$paymentCode);

        $this->assertNoApiError($res, 'DeleteBill');
        $this->assertSame('ok', strtolower((string)$res->res));
        self::$deleted = true;

        $deletedBill = self::$api->getBillByReference(self::$billReference);
        $this->assertApiError($deletedBill, 'Deleted bill should not be returned by reference lookup.');
    }

    private function sampleBill()
    {
        $bill = new Bill();
        $bill->amount = self::CREATED_AMOUNT;
        $bill->customerCode = self::CUSTOMER_CODE;
        $bill->customerName = self::CREATED_CUSTOMER_NAME;
        $bill->time = date('Y-m-d H:i');
        $bill->description = self::DESCRIPTION;
        $bill->billReference = self::$billReference;

        return $bill;
    }

    private function assertNoApiError($res, string $operation)
    {
        $this->assertIsObject($res, "$operation should return an object response.");
        $this->assertTrue(
            empty($res->error),
            "$operation error: " . ($res->error ?? 'unknown')
        );
    }

    private function assertApiError($res, string $operation)
    {
        $this->assertIsObject($res, "$operation should return an object response.");
        $this->assertTrue(
            !empty($res->error) || !empty($res->errorCode),
            "$operation should return an API error response."
        );
    }

    private function assertBillMatchesExpected($bill)
    {
        $this->assertSame(self::$billReference, $bill->billReference);
        $this->assertSame(self::$merchantId, $bill->merchantID);
        $this->assertSame(strtoupper(self::CUSTOMER_CODE), $bill->customerCode);
        $this->assertSame(self::UPDATED_CUSTOMER_NAME, $bill->customerName);
        $this->assertSame(self::DESCRIPTION, $bill->description);
        $this->assertSame(0, $bill->paymentStatus);
        $this->assertSame(
            $this->normalizePaymentCode(self::$paymentCode),
            $this->normalizePaymentCode($bill->wbcCode)
        );
        $this->assertEqualsWithDelta((float)self::UPDATED_AMOUNT, (float)$bill->amount, 0.001);
        $this->assertNotEmpty($bill->updateTimeStamp);
    }

    private function normalizePaymentCode(string $paymentCode): string
    {
        return preg_replace('/\D+/', '', $paymentCode);
    }

    private function findBillByReference(array $bills, string $billReference)
    {
        foreach ($bills as $bill) {
            if (($bill->billReference ?? null) === $billReference) {
                return $bill;
            }
        }

        return null;
    }

    private function cursorBefore(string $updateTimeStamp): string
    {
        $base = substr($updateTimeStamp, 0, 14);
        $date = \DateTime::createFromFormat('YmdHis', $base, new \DateTimeZone('UTC'));

        if (!$date) {
            return '';
        }

        $date->modify('-1 second');
        return $date->format('YmdHis');
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
