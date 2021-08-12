<?php

require 'vendor/autoload.php';

use WeBirr\Bill;
use WeBirr\WeBirrClient;

// const API_KEY = 'YOUR_API_KEY';
// const MERCHANT_ID = 'YOUR_MERCHANT_ID';

$apiKey = getenv('wb_apikey_1'); //'YOUR_API_KEY';
$merchantId = getenv('wb_merchid_1');  //'YOUR_MERCHANT_ID';

$api = new WeBirrClient($apiKey, true);

$bill = new Bill();

$bill->amount = '270.90';
$bill->customerCode = 'cc01';  // it can be email address or phone number if you dont have customer code
$bill->customerName =  'Elias Haileselassie';
$bill->time = '2021-07-22 22:14'; // your bill time, always in this format
$bill->description = 'hotel booking';
$bill->billReference = 'php/2021/125'; // your unique reference number
$bill->merchantID = $merchantId;

echo 'Creating Bill...';

$res = $api->createBill($bill);

var_dump($res);

//$api->createBillAsync($bill, function($resp) { echo $resp; } );

