<?php

require 'vendor/autoload.php';

use WeBirr\SupportedBank;
use WeBirr\WeBirrClient;

// Get banks and wallets configured for the merchant.
function main()
{
    $apiKey = getenv('WEBIRR_TEST_ENV_API_KEY') !== false ? getenv('WEBIRR_TEST_ENV_API_KEY') : "";
    $merchantId = getenv('WEBIRR_TEST_ENV_MERCHANT_ID') !== false ? getenv('WEBIRR_TEST_ENV_MERCHANT_ID') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID';

    $api = new WeBirrClient($merchantId, $apiKey, true);

    echo "\nGetting supported banks...";

    $response = $api->getSupportedBanks();

    if (!$response->error) {
        // success
        foreach ($response->res as $obj) {
            $bank = new SupportedBank($obj);
            echo "\n" . $bank->bankID . " - " . $bank->name;
        }
    } else {
        // error
        echo "\nError: " . $response->error;
        echo "\nError Code: " . $response->errorCode;
    }
}

main();
