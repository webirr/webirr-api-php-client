Official PHP Client Library for WeBirr Payment Gateway APIs

This Client Library provides convenient access to WeBirr Payment Gateway APIs from PHP Applications.

## Install

```bash
$ composer require webirr/webirr
```

## Usage

The library needs to be configured with a *merchant Id* & *API key*. You can get it by contacting [webirr.com](https://webirr.net)

> You can use this library for production or test environments. you will need to set isTestEnv=true for test, and false for production apps when creating objects of class WeBirrClient

## Example

### Creating a new Bill / Updating an existing Bill on WeBirr Servers

```php
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
    $bill->billReference = 'php/2021/128'; // your unique reference number
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

```

### Getting Payment status of an existing Bill from WeBirr Servers

```php
<?php

require 'vendor/autoload.php';

use WeBirr\WeBirrClient;
use WeBirr\PaymentDetail;

// Get Payment Status of a Bill
function main()
{

    $apiKey = getenv('wb_apikey_1') !== false ? getenv('wb_apikey_1') : "";
    $merchantId = getenv('wb_merchid_1') !== false ? getenv('wb_merchid_1') : "";

    //$apiKey = 'YOUR_API_KEY';
    //$merchantId = 'YOUR_MERCHANT_ID';

    $api = new WeBirrClient($merchantId, $apiKey, true);

    $paymentCode = '149 233 514';   // PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL

    echo "\nGetting Payment Status...";

    $res = $api->getPaymentStatus($paymentCode);

    if (!$res->error) {
        // success
        if ($res->res->status == 2) {  // 0. Pending  ( $res->res->data will be null !), 1. Payment in Progress (unconfirmed payment) 2. Paid
          $payment = new PaymentDetail($res->res->data);
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

```

*Sample object returned from getPaymentStatus()*

```javascript
{
  error: null,
  res: {
    status: 2,
        data: {
            status: 2,
            id: 111219507,
            bankID: "cbe_mobile",
            paymentReference: "TX70e78862148f4c249606",
            paymentDate: "2025-02-26 22:17:19",
            confirmed: true,
            confirmedTime: "2025-02-26 22:17:19",
            amount: "278",
            wbcCode: "149 233 514",
            updateTimeStamp: "2025022622171981338"
        }
    },
    errorCode: null
}

```

### Deleting an existing Bill from WeBirr Servers (if it is not paid)

```php
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

    $paymentCode =  '460 609 416'; // PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL

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

```

### Getting list of Payments and process them with Bulk Polling Consumer

```php

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
            $this->FetchPayments();
            echo "\nSleeping for 5 seconds...";
            sleep(5); // Sleep for 5 seconds before the next polling
        }
    }

    private function FetchPayments()
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
                $this->ProcessPayment($payment);
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

    // Process Payment should be impleneted as idempotent operation for production use cases
    private function ProcessPayment(Payment $payment)
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

```

### Gettting basic Statistics about bills created and payments received for a date range 

```php

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

```