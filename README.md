Official PHP Client Library for WeBirr Payment Gateway APIs

This Client Library provides convenient access to WeBirr Payment Gateway APIs from PHP Applications.

## Install

```bash
$ composer require webirr/webirr
```

## Usage

The library needs to be configured with a *merchant Id* & *API key*. You can get it by contacting [webirr.com](https://webirr.com)

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
    $apiKey = 'YOUR_API_KEY';
    $merchantId = 'YOUR_MERCHANT_ID';

    //$apiKey = getenv('wb_apikey_1');
    //$merchantId = getenv('wb_merchid_1');

    $api = new WeBirrClient($apiKey, true);

    $bill = new Bill();

    $bill->amount = '270.90';
    $bill->customerCode = 'cc01';  // it can be email address or phone number if you dont have customer code
    $bill->customerName =  'Elias Haileselassie';
    $bill->time = '2021-07-22 22:14'; // your bill time, always in this format
    $bill->description = 'hotel booking';
    $bill->billReference = 'php/2021/127'; // your unique reference number
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

```

*Sample object returned from getPaymentStatus()*

```javascript
{
  error: null,
  res: {
    status: 2,
    data: {
      id: 111112347,
      paymentReference: '8G3303GHJN',      
      confirmed: true,
      confirmedTime: '2021-07-03 10:25:35',
      bankID: 'cbe_birr',
      time: '2021-07-03 10:25:33',
      amount: '4.60',
      wbcCode: '624 549 955'
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
    $apiKey = 'YOUR_API_KEY';
    $merchantId = 'YOUR_MERCHANT_ID';

    //$apiKey = getenv('wb_apikey_1');
    //$merchantId = getenv('wb_merchid_1');

    $api = new WeBirrClient($apiKey, true);

    $paymentCode = 'PAYMENT_CODE_YOU_SAVED_AFTER_CREATING_A_NEW_BILL';  // suchas as '141 263 782';

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