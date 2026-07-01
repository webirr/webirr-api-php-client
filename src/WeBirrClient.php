<?php

namespace WeBirr;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use RuntimeException;

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

  private const TEST_BASE_URL = 'https://api.webirr.dev';
  private const PROD_BASE_URL = 'https://api.webirr.net:8080';

  private string $merchantId;
  private string $apiKey;
  private string $baseUrl;
  private $client;

  /**
   * Creates an instance of WeBirrClient object to interact with remote WebService API.
   * @param {string} merchantId
   * @param {string} apiKey 
   * @param {boolean} isTestEnv 
   * @param {ClientInterface|null} client Optional configured Guzzle client for connection reuse/custom handlers.
   */
  public function __construct(string $merchantId, string $apiKey, bool $isTestEnv, ?ClientInterface $client = null)
  {
    $this->merchantId = $merchantId;
    $this->apiKey = $apiKey;
    $this->baseUrl = $this->resolveBaseUrl($isTestEnv);
    $this->client = $client ?: new Client();
  }

  private function resolveBaseUrl(bool $isTestEnv): string
  {
    if (!$isTestEnv) {
      return self::PROD_BASE_URL;
    }

    $gatewayUrl = trim((string)getenv('GATEWAY_URL'));
    if ($gatewayUrl !== '') {
      return rtrim($gatewayUrl, '/');
    }

    return self::TEST_BASE_URL;
  }

  private function url(string $path, array $params = []): string
  {
    return $this->baseUrl . '/' . ltrim($path, '/') . '?' . $this->query($params);
  }

  private function query(array $params = []): string
  {
    $query = [
      'api_key' => $this->apiKey
    ];

    if ($this->merchantId !== '') {
      $query['merchant_id'] = $this->merchantId;
    }

    return http_build_query(
      array_merge(
        $query,
        $params
      ),
      '',
      '&',
      PHP_QUERY_RFC3986
    );
  }

  private function prepareBill(Bill $bill): Bill
  {
    if ($this->merchantId !== '') {
      $bill->merchantID = $this->merchantId;
    }

    return $bill;
  }

  private function decodeResponse($response)
  {
    $statusCode = $response->getStatusCode();
    if ($statusCode < 200 || $statusCode >= 300) {
      throw new RuntimeException(trim('HTTP error ' . $statusCode . ' ' . $response->getReasonPhrase()));
    }

    $body = (string)$response->getBody();
    if (trim($body) === '') {
      throw new RuntimeException('Empty JSON response');
    }

    $payload = json_decode($body);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
    }

    if (!is_object($payload)) {
      throw new RuntimeException('WeBirr API response must be a JSON object');
    }

    return $payload;
  }

  private function requestOptions(array $options = []): array
  {
    return array_merge($options, ['http_errors' => true]);
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
    $response = $this->client->request('POST', $this->url('einvoice/api/bill'), $this->requestOptions(['json' => $bill->toArray()]));

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
    $response = $this->client->request('PUT', $this->url('einvoice/api/bill'), $this->requestOptions(['json' => $bill->toArray()]));

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
    $response = $this->client->request(
      'DELETE',
      $this->url('einvoice/api/bill', ['wbc_code' => $paymentCode]),
      $this->requestOptions(['json' => []])
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
    $response = $this->client->request('GET', $this->url('einvoice/api/paymentStatus', ['wbc_code' => $paymentCode]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

  /**
   * Get one bill by the merchant bill reference.
   * @param {string} billReference The merchant's unique bill reference.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res will contain the bill details on success.
   */
  public function getBillByReference(string $billReference)
  {
    $response = $this->client->request('GET', $this->url('einvoice/api/bill', ['bill_reference' => $billReference]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

  /**
   * Get one bill by WeBirr payment code / WBC code.
   * @param {string} paymentCode The payment code returned by createBill.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res will contain the bill details on success.
   */
  public function getBillByPaymentCode(string $paymentCode)
  {
    $response = $this->client->request('GET', $this->url('einvoice/api/bill', ['wbc_code' => $paymentCode]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

  /**
   * Get list of Payments from WeBirr Servers received after the last processed timestamp ( for bulk polling )
   * The caller should track the last retrieved payment updateTimeStamp to prevent duplicate retrievals.
   * On first-time calls, prefer a recent saved cursor such as "20251231" or include time when needed, for example "20251231235959".
   * Use empty string "" only when intentionally scanning from the beginning.
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
    $response = $this->client->request('GET', $this->url('einvoice/api/payments', ['last_timestamp' => $lastTimeStamp, 'limit' => $limit]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

  /**
   * Get list of bills updated after the last processed timestamp.
   * @param {int} paymentStatus -1 for all, 0 pending, 1 unconfirmed payment, 2 paid.
   * @param {string} lastTimeStamp Timestamp cursor. Prefer a saved or recent cursor such as "20251231"; include time when needed, for example "20251231235959". Empty string means from the beginning.
   * @param {int} limit The number of bills returned per request.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res? will contain an array of bills on success.
   */
  public function getBills(int $paymentStatus = -1, string $lastTimeStamp = "", int $limit = 100)
  {
    $response = $this->client->request('GET', $this->url('einvoice/api/bills', [
      'payment_status' => $paymentStatus,
      'last_timestamp' => $lastTimeStamp,
      'limit' => $limit
    ]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

  /**
   * Get banks and wallets configured for this merchant.
   * @returns {object/stdClass/ApiResponse} ApiResponse.res? will contain an array of SupportedBank objects on success.
   */
  public function getSupportedBanks()
  {
    $response = $this->client->request('GET', $this->url('einvoice/api/banks'), $this->requestOptions());

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
    $response = $this->client->request('GET', $this->url('merchant/stat', ['date_from' => $dateFrom, 'date_to' => $dateTo]), $this->requestOptions());

    return $this->decodeResponse($response);
  }

}
