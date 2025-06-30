<?php
namespace WeBirr\Tests;

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use WeBirr\Bill;
use WeBirr\WeBirrClient;

class WeBirrTest_ext extends TestCase
{
    static $paymentCode = null;
    static $billReference = null;
    static $api = null;

    public static function setUpBeforeClass(): void
    {
        self::$billReference = 'php/test/' . time();
        $apiKey = getenv('wb_apikey_1') ?: '';
        $merchantId = getenv('wb_merchid_1') ?: '';

        if (empty($apiKey) || empty($merchantId)) {
            self::fail(
                "Environment variables wb_apikey_1 and wb_merchid_1 must be set and non-empty for integration tests."
            );
        }

        self::$api = new WeBirrClient($merchantId, $apiKey, true);
    }

    public function testCreateBill()
    {
        $bill = $this->sampleBill();
        $res = self::$api->createBill($bill);
        $this->assertEmpty($res->error, "CreateBill error: {$res->error}");
        self::$paymentCode = $res->res;
        $this->assertNotEmpty(self::$paymentCode, "Payment code should not be empty");
    }

    /**
     * @depends testCreateBill
     */
    public function testUpdateBill()
    {
        $bill = $this->sampleBill();
        $bill->amount = '278.00';
        $bill->customerName = 'Elias php';
        $res = self::$api->updateBill($bill);
        $this->assertEmpty($res->error, "UpdateBill error: {$res->error}");
    }

    /**
     * @depends testCreateBill
     */
    public function testGetPaymentStatus()
    {
        $this->assertNotEmpty(self::$paymentCode, "Payment code not set");
        $res = self::$api->getPaymentStatus(self::$paymentCode);
        $this->assertEmpty($res->error, "GetPaymentStatus error: {$res->error}");
        $this->assertEquals($res->res->status, 0, "Expected status 0 (Pending), got {$res->res->status}");
    }

    /**
     * @depends testCreateBill
     */
    public function testDeleteBill()
    {
        $this->assertNotEmpty(self::$paymentCode, "Payment code not set");
        $res = self::$api->deleteBill(self::$paymentCode);
        $this->assertEmpty($res->error, "DeleteBill error: {$res->error}");
    }

    private function sampleBill()
    {
        $bill = new Bill();
        $bill->amount = '270.90';
        $bill->customerCode = 'cc01';
        $bill->customerName = 'Elias Haileselassie';
        $bill->time = date('Y-m-d H:i');
        $bill->description = 'hotel booking';
        $bill->billReference = self::$billReference;
        $bill->merchantID = getenv('wb_merchid_1') ?: '';
        return $bill;
    }
}