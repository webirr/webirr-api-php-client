<?php

use WeBirr;

//$api = new WeBirrClient('x', true);

//echo $api->Ping();


$bill = new Bill();

$bill->$amount = '120.6';
$bill->$customerCode = 'cc01';
$bill->$customerName = 'Elias h';

$myJSON = json_encode($bill);

echo $myJSON;
