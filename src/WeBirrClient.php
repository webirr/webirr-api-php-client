<?php 

namespace WeBirr;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class WeBirrClient {
    
  private $apiKey;
  private $client;

  public function __construct($apiKey, $isTestEnv)
  {
    $this->$apiKey = $apiKey; 

    $client = new Client([
        // Base URI is used with relative requests
        'base_uri' => 'http://httpbin.org',
        // You can set any number of default request options.
        'timeout'  => 10.0,
    ]);
      
  }
  
  public function Ping(){
      return 'Ping from WeBirrClient';
  }

}

?>
