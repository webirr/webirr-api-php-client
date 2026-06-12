<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;

// Get a single bill by reference or payment code, and list bills by payment status.
function main()
{
    $apiKey = getenv('WEBIRR_TEST_ENV_API_KEY') !== false ? getenv('WEBIRR_TEST_ENV_API_KEY') : "";
    $merchantId = getenv('WEBIRR_TEST_ENV_MERCHANT_ID') !== false ? getenv('WEBIRR_TEST_ENV_MERCHANT_ID') : "";

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $billReference = getenv('WEBIRR_TEST_BILL_REFERENCE') !== false ? getenv('WEBIRR_TEST_BILL_REFERENCE') : "YOUR_BILL_REFERENCE";
    $paymentCode = getenv('WEBIRR_TEST_PAYMENT_CODE') !== false ? getenv('WEBIRR_TEST_PAYMENT_CODE') : "YOUR_PAYMENT_CODE";

    echo "\nGetting bill by reference...";
    $response = $api->getBillByReference($billReference);
    if (!$response->error) {
        echo "\nBill found by reference.";
        echo "\n";
        print_r($response->res);
    } else {
        echo "\nError: " . $response->error;
        echo "\nError Code: " . $response->errorCode;
    }

    echo "\nGetting bill by payment code...";
    $response = $api->getBillByPaymentCode($paymentCode);
    if (!$response->error) {
        echo "\nBill found by payment code.";
        echo "\n";
        print_r($response->res);
    } else {
        echo "\nError: " . $response->error;
        echo "\nError Code: " . $response->errorCode;
    }

    echo "\nListing bills...";
    $paymentStatus = -1; // -1 all, 0 pending, 1 unconfirmed payment, 2 paid.
    $lastTimeStamp = ""; // Empty string starts from the beginning.
    $limit = 10;

    $response = $api->getBills($paymentStatus, $lastTimeStamp, $limit);
    if (!$response->error) {
        echo "\nBills returned: " . count($response->res);
        foreach ($response->res as $bill) {
            echo "\n-----------------------------";
            print_r($bill);
        }
    } else {
        echo "\nError: " . $response->error;
        echo "\nError Code: " . $response->errorCode;
    }
}

main();
