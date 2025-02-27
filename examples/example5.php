<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;
use WeBirr\Stat;

// Get basic statistics about bills created and payments received for a date range 
function main()
{
    $apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
    $merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID'; 

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $dateFrom = '2025-01-01'; // YYYY-MM-DD
    $dateTo = '2030-01-31'; // YYYY-MM-DD
    
    echo "\nRetrieving Statistics...";
    echo "\nDate From: $dateFrom";
    echo "\nDate To: $dateTo";

    $response = $api->getStat($dateFrom, $dateTo);

    if (!$response->error){
        //success
        $stat = new Stat($response->res);

        echo "\nNumber of Bills Created: " . $stat->nBills;
        echo "\nNumber of Paid Bills: " . $stat->nBillsPaid;
        echo "\nNumber of Unpaid Bills: " . $stat->nBillsUnpaid;
        echo "\nAmount of Bills: " . $stat->amountBills;
        echo "\nAmount Paid: " . $stat->amountPaid;
        echo "\nAmount Unpaid: " . $stat->amountUnpaid;
    } else {
        // error
        echo "\nError: " . $response->error;    
    }
}

main();

