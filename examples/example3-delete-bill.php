<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;

// Delete an existing Bill
function main()
{

    $apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
    $merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID';

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $paymentCode =  '379 262 100'; // PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL

    echo "\nDeleting Bill...";

    $res = $api->deleteBill($paymentCode);

    if (!$res->error) {
        // success
        echo "\nbill is deleted succesfully"; //res.res will be 'OK'  no need to check here!
      } else {
        // fail
        echo "\nerror: $res->error";
        echo "\nerrorCode: $res->errorCode"; // can be used to handle specific bussines error such as ERROR_INVLAID_INPUT
      }
    //var_dump($res);
}

main();
