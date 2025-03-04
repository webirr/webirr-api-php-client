<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;
use WeBirr\Payment;

// Get list of Payments and process them with bulk polling consumer
class PaymentProcessor
{
    private $apiKey;
    private $merchantId;
    private $api;
    private $lastTimeStamp;

    public function __construct()
    {
        $this->apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
        $this->merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";
        $this->api = new WeBirrClient($this->merchantId, $this->apiKey, true);
        $this->lastTimeStamp = '20250224120000'; // Example timestamp, replace with your actual last timestamp retrieved from your database to current date stamp for first time call
    }

    public function Run()
    {
        while (true) {
            echo "\nRetrieving Payments...";
            $this->fetchAndProcessPayments();
            echo "\nSleeping for 5 seconds...";
            sleep(5); // Sleep for 5 seconds before the next polling
        }
    }

    private function fetchAndProcessPayments()
    {
        $limit = 100;  // Number of records to retrieve depending on your processing requirement & capacity
        $response = $this->api->getPayments($this->lastTimeStamp, $limit);

        if (!$response->error) {
            // success
            if (count($response->res) == 0) {
                echo "\nNo new payments found.";
            }
            foreach ($response->res as $obj) {
                $payment = new Payment($obj);
                $this->processPayment($payment);
                echo "\n-----------------------------";
            }

            if (count($response->res) > 0) {
                $this->lastTimeStamp = $response->res[count($response->res) - 1]->updateTimeStamp;
                echo "\nLast Timestamp: " . $this->lastTimeStamp; // save this to your database for next polling/call to getPayments()
            }

        } else {
            // fail
            echo "\nerror: " . $response->error;
            echo "\nerrorCode: " . $response->errorCode; // can be used to handle specific business error such as ERROR_INVALID_INPUT
        }
    }

    /**
      * Process Payment should be impleneted as idempotent operation for production use cases
      * This method and logic can be shared among all payment processing consumers: 1. bulk polling, 2. webhook, 3. single payment polling.
     */
    private function processPayment(Payment $payment)
    {
        echo "\nPayment Status: " . $payment->status;
        if ($payment->IsPaid()) {
            echo "\nPayment Status Text: Paid.";
        }
        if ($payment->IsReversed()) {
            echo "\nPayment Status Text: Reversed.";
        }
        echo "\nBank: " . $payment->bankID;
        echo "\nBank Reference Number: " . $payment->paymentReference;
        echo "\nAmount Paid: " . $payment->amount;
        echo "\nPayment Date: " . $payment->paymentDate;
        echo "\nReversal/Cancel Date: " . $payment->canceledTime;
        echo "\nUpdate Timestamp: " . $payment->updateTimeStamp;
    }
}

// Run the payment processor
$processor = new PaymentProcessor();
$processor->Run();