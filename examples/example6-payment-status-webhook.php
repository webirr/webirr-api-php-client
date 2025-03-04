<?php

require 'vendor/autoload.php';

use WeBirr\Payment;

// Webhook handler for processing payment updates from WeBirr.
// This script should be hosted on a secure server with HTTPS enabled.

class Webhook
{
    /**
     * Handle incoming webhook POST requests.
     *  Validates request method (must be POST).
     *  Checks authentication using the authKey from the query string, otherwise system will not know if the request is coming from WeBirr(authorized) or not
     */
    public function handleRequest()
    {
        // Validate request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);  // Method Not Allowed
            header('Content-Type: application/json');
            echo json_encode(["error" => "Method Not Allowed. POST required."]);
            return;
        }

        // Authenticate using authKey query string parameter, otherwise system will not know if the request is from WeBirr(authorized) or not
        if (!$this->isAuthenticated()) {
            http_response_code(403);  
            header('Content-Type: application/json');
            echo json_encode(["error" => "Unauthorized access. Invalid authKey."]);
            return;
        }

        // Read the raw JSON input
        $rawPayload = file_get_contents('php://input');
        if (empty($rawPayload)) {
            http_response_code(400);  // Bad Request
            header('Content-Type: application/json');
            echo json_encode(["error" => "Empty request body."]);
            return;
        }

        // Decode the JSON payload into an associative array
        $jsonBody = json_decode($rawPayload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(["error" => "Invalid JSON format."]);
            return;
        }

        try {
            $payment = new Payment($jsonBody['data']);
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(["error" => "Invalid payment data: " . $e->getMessage()]);
            return;
        }

        // Process the payment asynchronously (or enqueue it for later processing)
        // here instead of immediate processing, this should be handled asynchronously or enqueued to a background worker/job.
        $this->processPayment($payment);

        // Return a JSON success response (can also be just empty body with 200 OK status) 
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(["success" => true, "message" => "Payment received and queued for processing"]);
    }

    /**
     * Authenticate the request using authKey from the query string.
     * 
     * The authKey is compared against the environment variable `WB_WEBHOOK_AUTH_KEY`.
     * 
     * @return bool True if authentication succeeds, false otherwise.
     */
    private function isAuthenticated(): bool
    {
        // Retrieve the authKey from the query string
        $providedAuthKey = $_GET['authKey'] ?? '';
        
        // TODO: Replace this with your own auth key (preferably from environment variable)
         $expectedAuthKey = "please-change-me-to-secure-key-5114831AFD5D4646901DCDAC58B92F8E";
        //$expectedAuthKey = getenv('wb_webhook_authkey') !== false ? getenv('wb_webhook_authkey') : ""; 
    
        // Compare the provided key with the expected key
        return !empty($expectedAuthKey) && hash_equals($expectedAuthKey, $providedAuthKey);
    }
    
     /**
      *  Process Payment should be impleneted as idempotent operation for production use cases
      *  Prefered approach is: Payment should be processed asynchronously or enqueued to a background worker.
      *  This method and logic can be shared among all payment processing consumers: 1. bulk polling, 2. webhook, 3. single payment polling.
      * @param Payment $payment The Payment object.
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

// Instantiate and handle the webhook request. once hosted, the url needs to be shared with WeBirr for configuration
$webhook = new Webhook();
$webhook->handleRequest();