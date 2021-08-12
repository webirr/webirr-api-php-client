<?php

require 'vendor/autoload.php';

use WeBirr\Bill;
use WeBirr\WeBirrClient;

// Get Payment Status of Bill
function main()
{
    $apiKey = 'YOUR_API_KEY';
    $merchantId = 'YOUR_MERCHANT_ID';

    //$apiKey = getenv('wb_apikey_1');
    //$merchantId = getenv('wb_merchid_1');

    $api = new WeBirrClient($apiKey, true);

    $paymentCode = 'PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL';  // suchas as '141 263 782';

    echo "\nGetting Payment Status...";

    $res = $api->getPaymentStatus($paymentCode);

    if (!$res->error) {
        // success
        if ($res->res->status == 2) {
          $data =  $res->res->data;
          echo "\nbill is paid";
          echo "\nbill payment detail";
          echo "\nBank: $data->bankID";
          echo "\nBank Reference Number: $data->paymentReference";
          echo "\nAmount Paid: $data->amount";
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
