<?php

namespace WeBirr\Tests;

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use WeBirr\Bill;
use WeBirr\WeBirrClient;

class WeBirrTest extends TestCase
{
    public function testCreateBillUsesClientMerchantIdBeforeSending()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('merchant-from-client', 'x', true);

        $api->createBill($bill);

        $this->assertSame('merchant-from-client', $bill->merchantID);
    }

    public function testCreateBillShouldGetErrorFromWebServiceOnInvalidApiKeyTestEnv()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->createBill($bill);

        $this->assertApiError($res);
    }

    public function testUpdateBillShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->updateBill($bill);

        $this->assertApiError($res);
    }

    public function testDeleteBillShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->deleteBill('xxxx');

        $this->assertApiError($res);
    }

    public function testGetPaymentStatusShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getPaymentStatus('xxxx');

        $this->assertApiError($res);
    }

    public function testGetBillByReferenceShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBillByReference('missing-reference');

        $this->assertApiError($res);
    }

    public function testGetBillByPaymentCodeShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBillByPaymentCode('xxxx');

        $this->assertApiError($res);
    }

    public function testGetBillsShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getBills(-1, '', 10);

        $this->assertApiError($res);
    }

    public function testGetPaymentsShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getPayments('', 10);

        $this->assertApiError($res);
    }

    public function testGetStatShouldGetErrorFromWebServiceOnInvalidApiKey()
    {
        $api = new WeBirrClient('x', 'x', true);
        $res = $api->getStat('2025-01-01', '2025-01-02');

        $this->assertApiError($res);
    }

    private function sampleBill()
    {
        $bill = new Bill();
        $bill->amount = '270.90';
        $bill->customerCode = 'sdk-test-customer';
        $bill->customerName = 'SDK Test Customer';
        $bill->time = date('Y-m-d H:i');
        $bill->description = 'SDK test bill';
        $bill->billReference = 'php/unit/' . time();

        return $bill;
    }

    private function assertApiError($res)
    {
        $this->assertIsObject($res);
        $this->assertTrue(
            !empty($res->error) || !empty($res->errorCode),
            'Expected API error response.'
        );
    }
}
