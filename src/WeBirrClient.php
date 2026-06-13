<?php

namespace WeBirr;

use GuzzleHttp\Client;

/*
class ApiResponse 
{
  public $error; //String
  public $res;   // String | Payment
  public $errorCode; // String
}
*/

/** 
 * A WeBirrClient instance object can be used to
 * Create, Update or Delete a Bill at WeBirr Servers and also to
 * Get the Payment Status of a bill.
 * It is a wrapper for the REST Web Service API.
 */
class WeBirrClient
{

  private string $merchantId;
  private string $apiKey;
  private $client;

  /**
   * Creates an instance of WeBirrClient object to interact with remote WebService API.
   * @param {string} merchantId
   * @param {string} apiKey 
   * @param {boolean} isTestEnv 
   */
  public function __construct(string $merchantId, string $apiKey, bool $isTestEnv)
  {
    $this->merchantId = $merchantId;
    $this->apiKey = $apiKey;
    $this->client = new Client(['base_uri' => $isTestEnv ? 'https://api.webirr.net/' : 'https://api.webirr.net:8080/']);
  }

  private function query(array $params = []): string
  {
    return http_build_query(
      array_merge(
        [
          'api_key' => $this->apiKey,
          'merchant_id' => $this->merchantId
        ],
        $params
      ),
      '',
      '&',
      PHP_QUERY_RFC3986
    );
  }

  private function prepareBill(Bill $bill): Bill
  {
    $bill->merchantID = $this->merchantId;
    return $bill;
  }

  private function decodeResponse($response)
  {
    if ($response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return ['error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase()];
  }

  /** 
   * Create a new bill at WeBirr Servers.
   * @param {Bill} bill represents an invoice or bill for a customer. see sample for structure of the Bill
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse object 
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have the value of the returned PaymentCode on success.
   */
  public function createBill(Bill $bill)
  {
    $bill = $this->prepareBill($bill);
    $response = $this->client->post('einvoice/api/bill?' . $this->query(), ['json' => $bill->toArray()]);

    return $this->decodeResponse($response);
  }
  /**  
   * Update an existing bill at WeBirr Servers, if the bill is not paid yet.
   * The billReference has to be the same as the original bill created.
   * @param {object} bill represents an invoice or bill for a customer. see sample for structure of the Bill
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse Object 
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have the value of "OK" on success.
   */
  public function updateBill(Bill $bill)
  {
    $bill = $this->prepareBill($bill);
    $response = $this->client->put('einvoice/api/bill?' . $this->query(), ['json' => $bill->toArray()]);

    return $this->decodeResponse($response);
  }

  /** 
   * Delete an existing bill at WeBirr Servers, if the bill is not paid yet.
   * @param {string} paymentCode is the number that WeBirr Payment Gateway returns on createBill.
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse Object 
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have the value of "OK" on success.
   */
  public function deleteBill(string $paymentCode)
  {
    $response = $this->client->delete(
      'einvoice/api/bill?' . $this->query(['wbc_code' => $paymentCode]),
      ['json' => []]
    );

    return $this->decodeResponse($response);
  }

  /**
   * Get Payment Status of a Bill from WeBirr Servers
   * @param {string} paymentCode is the number that WeBirr Payment Gateway returns on createBill.
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse Object  
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have `Payment` object on success (will be null otherwise!)
   * ApiResponse.res?.isPaid ?? false -> will return true if the bill is paid (payment completed)
   * ApiResponse.res?.status -> will return 0 if the bill is pending payment, 1 if payment is in progress (unconfirmed), 2 if paid
   */
  public function getPaymentStatus(string $paymentCode)
  {
    $response = $this->client->get('einvoice/api/paymentStatus?' . $this->query(['wbc_code' => $paymentCode]));

    return $this->decodeResponse($response);
  }

  /**
   * Get one bill by the merchant bill reference.
   * @param {string} billReference The merchant's unique bill reference.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res will contain the bill details on success.
   */
  public function getBillByReference(string $billReference)
  {
    $response = $this->client->get('einvoice/api/bill?' . $this->query(['bill_reference' => $billReference]));

    return $this->decodeResponse($response);
  }

  /**
   * Get one bill by WeBirr payment code / WBC code.
   * @param {string} paymentCode The payment code returned by createBill.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res will contain the bill details on success.
   */
  public function getBillByPaymentCode(string $paymentCode)
  {
    $response = $this->client->get('einvoice/api/bill?' . $this->query(['wbc_code' => $paymentCode]));

    return $this->decodeResponse($response);
  }

  /**
   * Get list of Payments from WeBirr Servers received after the last processed timestamp ( for bulk polling )
   * The caller should track the last retrieved payment updateTimeStamp to prevent duplicate retrievals.
   * on firt time calls, lastTimeStamp can be empty string "" or current dateTime with any precesion formated as IntString "20250227" or "20250227135959".
   * This API can be used to track paid (confirmed) as well as reversed payment transactions.
   * Polling implementations should gracefully handle the rare case of redundant read to the same record.
   * @param {string} lastTimeStamp The updateTimeStamp field value of the last payment record in the array retrieved before.
   * @param {int} limit The number of records returned per request based on the caller's processing capacity.
   * @returns {object/stdClass/ApiResponse} See example for structure of the returned ApiResponse Object.
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res? will have an array of `Payment` objects or empty array [] on success ( will be null on error!).
   * ApiResponse.res?[i].status -> will return 2 if payment is a confirmed payment(Paid) or 3 if payment is reversed/canceled.
   */
  public function getPayments(string $lastTimeStamp,int $limit)
  {
    $response = $this->client->get('einvoice/api/payments?' . $this->query(['last_timestamp' => $lastTimeStamp, 'limit' => $limit]));

    return $this->decodeResponse($response);
  }

  /**
   * Get list of bills updated after the last processed timestamp.
   * @param {int} paymentStatus -1 for all, 0 pending, 1 unconfirmed payment, 2 paid.
   * @param {string} lastTimeStamp Timestamp cursor. Empty string means from the beginning.
   * @param {int} limit The number of bills returned per request.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res? will contain an array of bills on success.
   */
  public function getBills(int $paymentStatus = -1, string $lastTimeStamp = "", int $limit = 100)
  {
    $response = $this->client->get('einvoice/api/bills?' . $this->query([
      'payment_status' => $paymentStatus,
      'last_timestamp' => $lastTimeStamp,
      'limit' => $limit
    ]));

    return $this->decodeResponse($response);
  }

  /**
   * Retrieves basic statistics about bills created and payments received over a date range
   * @param {string} dateFrom The start date of range (format: YYYY-MM-DD).
   * @param {string} dateTo The end date of range (format: YYYY-MM-DD).
   * @returns {object/stdClass/ApiResponse} The response object containing statistics or an error message.
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will ha statistics objects on success (will be null otherwise!).
   */
  public function getStat(string $dateFrom, string $dateTo)
  {
    $response = $this->client->get('merchant/stat?' . $this->query(['date_from' => $dateFrom, 'date_to' => $dateTo]));

    return $this->decodeResponse($response);
  }

}
