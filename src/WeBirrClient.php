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

class WeBirrClient {
    
  private $apiKey;
  private $client;

  public function __construct($apiKey, $isTestEnv)
  {
    $this->apiKey = $apiKey; 

    $this->client = new Client([
        'base_uri' => $isTestEnv ? 'https://api.webirr.com/' : 'https://api.webirr.com:8080/' ,
        //'timeout'  => 10.0,
    ]);
      
  }

  public function createBill(Bill $bill)
  {
    $response = $this->client->post('einvoice/api/postbill?api_key=' . $this->apiKey, 
    ['json' => $bill->toArray() ]); 

    if ( $response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return [ 'error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase() ];     
  }

  public function updateBill(Bill $bill)
  {
    $response = $this->client->put('einvoice/api/postbill?api_key=' . $this->apiKey,['json' => $bill->toArray()]);  

    if ( $response->getStatusCode() == 200)
      return json_decode($response->getBody());
    else
      return [ 'error' => 'http error ' . $response->getStatusCode() . $response->getReasonPhrase() ];     
  }
  
  // public function createBillAsync($bill, $callBack) 
  // {
  //   $promise = $this->client->postAsync('einvoice/api/postbill');  

  //   $promise->then(
  //     function (ResponseInterface $res) {
        
  //        $callBack($res);
  //         //echo $res->getStatusCode();
  //     },
  //     function (RequestException $e) {
  //       $callBack('error occured' . $e->getMessage());
  //         //echo $e->getMessage() . "\n";
  //         //echo $e->getRequest()->getMethod();
  //     }
  //   );

  // }

}
