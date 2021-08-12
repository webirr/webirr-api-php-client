<?php
//declare(strict_types=1);
namespace WeBirr\Tests;

require 'vendor/autoload.php';

use WeBirr\Bill;
use WeBirr\WeBirrClient;

use  \PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertTrue;

class WeBirrTest extends TestCase
{
    function test_CreateBill_should_get_error_from_WebService_on_invalid_api_key_TestEnv()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', true);
        $res = $api->createBill($bill);
        
        $this->assertTrue(strlen($res->errorCode) > 0);
    }

    function test_CreateBill_should_get_error_from_WebService_on_invalid_api_key_ProdEnv()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', false);
        $res = $api->createBill($bill);
        
        $this->assertTrue(strlen($res->errorCode) > 0);
    }
    
    function test_UpdateBill_should_get_error_from_WebService_on_invalid_api_key()
    {
        $bill = $this->sampleBill();
        $api = new WeBirrClient('x', true);
        $res = $api->updateBill($bill);
        
        $this->assertTrue(strlen($res->errorCode) > 0);
    }
    
    function test_DeleteBill_should_get_error_from_WebService_on_invalid_api_key()
    {
        $api = new WeBirrClient('x', true);
        $res = $api->deleteBill('xxxx');
        
        $this->assertTrue(strlen($res->error) > 0); // should contain error, erroCode is not implemented for deleteBill 
    }

    function test_GetPaymentStatus_should_get_error_from_WebService_on_invalid_api_key()
    {
        $api = new WeBirrClient('x', true);
        $res = $api->getPaymentStatus('xxxx');
        
        $this->assertTrue(strlen($res->errorCode) > 0); // should contain error 
    }

    private function sampleBill()
    {
        $bill = new Bill();
        $bill->amount = '270.90';
        $bill->customerCode = 'cc01';  // it can be email address or phone number if you dont have customer code
        $bill->customerName =  'Elias Haileselassie';
        $bill->time = '2021-07-22 22:14'; // your bill time, always in this format
        $bill->description = 'hotel booking';
        $bill->billReference = 'php/2021/127'; // your unique reference number
        $bill->merchantID = 'x';

        return $bill;
    }
}
