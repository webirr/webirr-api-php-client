<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;
use WeBirr\Payment;

// Get Payment Status of a Bill
function main()
{

    $apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
    $merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID';

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $paymentCode = '032 822 352';   // PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL

    echo "\nGetting Payment Status...";

    $res = $api->getPaymentStatus($paymentCode);

    if (!$res->error) {
        // success
        if ($res->res->status == 2) {  // 0. Pending  ( $res->res->data will be null !), 1. Payment in Progress (unconfirmed payment) 2. Paid
          $payment = new Payment($res->res->data);
          echo "\nbill is paid";
          echo "\nbill payment detail";
          echo "\nBank: $payment->bankID";
          echo "\nBank Reference Number: $payment->paymentReference";
          echo "\nAmount Paid: $payment->amount";
        } else
          echo "\nbill is pending payment";
      } else {
        // fail
        echo "\nerror: $res->error";
        echo "\nerrorCode: $res->errorCode"; // can be used to handle specific busines error such as ERROR_INVLAID_INPUT
      }

    //var_dump($res);
}

main();
