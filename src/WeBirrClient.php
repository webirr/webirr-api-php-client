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

  private $apiKey;
  private $client;

  /**
   * Creates an instance of WeBirrClient object to interact with remote WebService API.
   * @param {string} apiKey 
   * @param {boolean} isTestEnv 
   */
  public function __construct($apiKey, $isTestEnv)
  {
    $this->apiKey = $apiKey;
    $this->client = new Client(['base_uri' => $isTestEnv ? 'https://api.webirr.com/' : 'https://api.webirr.com:8080/']);
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
    $response = $this->client->post('einvoice/api/postbill?api_key=' . $this->apiKey, ['json' => $bill->toArray()]);

    if ($response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return ['error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase()];
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
    $response = $this->client->put('einvoice/api/postbill?api_key=' . $this->apiKey, ['json' => $bill->toArray()]);

    if ($response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return ['error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase()];
  }

  /** 
   * Delete an existing bill at WeBirr Servers, if the bill is not paid yet.
   * @param {string} paymentCode is the number that WeBirr Payment Gateway returns on createBill.
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse Object 
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have the value of "OK" on success.
   */
  public function deleteBill($paymentCode)
  {
    $response = $this->client->put(
      'einvoice/api/deletebill?api_key=' . $this->apiKey . '&wbc_code=' . $paymentCode,
      ['json' => []]
    );

    if ($response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return ['error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase()];
  }

  /**
   * Get Payment Status of a bill from WeBirr Servers
   * @param {string} paymentCode is the number that WeBirr Payment Gateway returns on createBill.
   * @returns {object/stdClass/ApiResponse} see sample for structure of the returned ApiResponse Object  
   * Check if(ApiResponse.error == null) to see if there are errors.
   * ApiResponse.res will have `Payment` object on success (will be null otherwise!)
   * ApiResponse.res?.isPaid ?? false -> will return true if the bill is paid (payment completed)
   * ApiResponse.res?.data ?? null -> will have `PaymentDetail` object
   */
  public function getPaymentStatus($paymentCode)
  {
    $response = $this->client->get('einvoice/api/getPaymentStatus?api_key=' . $this->apiKey . '&wbc_code=' . $paymentCode);

    if ($response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return ['error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase()];
  }
}
