<?php

require 'vendor/autoload.php';

use WeBirr\Bill;
use WeBirr\WeBirrClient;

// Create & Update Bill
function main()
{
    $apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
    $merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID';

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $bill = new Bill();

    $bill->amount = '270.90';
    $bill->customerCode = 'cc01';  // it can be email address or phone number if you dont have customer code
    $bill->customerName =  'Elias Haileselassie';
    $bill->time = '2021-07-22 22:14'; // your bill time, always in this format
    $bill->description = 'hotel booking';
    $bill->billReference = 'php/2021/132'; // your unique reference number
    $bill->merchantID = $merchantId;

    echo "\nCreating Bill...";

    $res = $api->createBill($bill);

    if (!$res->error) {
        // success
        $paymentCode = $res->res;  // returns paymentcode such as 429 723 975
        echo "\nPayment Code = $paymentCode"; // we may want to save payment code in local db.

    } else {
        // fail
        echo "\nerror: $res->error";
        echo "\nerrorCode: $res->errorCode"; // can be used to handle specific busines error such as ERROR_INVLAID_INPUT_DUP_REF
    }

    //var_dump($res);

    // Update existing bill if it is not paid
    $bill->amount = "278.00";
    $bill->customerName = 'Elias php';
    //$bill->billReference = "WE CAN NOT CHANGE THIS";

    echo "\nUpdating Bill...";

    $res = $api->updateBill($bill);

    if (!$res->error) {
        // success
        echo "\nbill is updated succesfully"; //res.res will be 'OK'  no need to check here!
    } else {
        // fail
        echo "\nerror: $res->error";
        echo "\nerrorCode: $res->errorCode"; // can be used to handle specific busines error such as ERROR_INVLAID_INPUT
    }
}

main();
