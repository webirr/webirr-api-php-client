<?php

use WeBirr\WeBirrClient;

$api = new WeBirrClient('x', true);

echo $api->Ping();