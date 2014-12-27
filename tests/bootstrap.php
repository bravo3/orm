<?php

use Predis\Client;

require(__DIR__.'/../vendor/autoload.php');

$redis = new Client(['host' => 'localhost']);
$redis->flushall();

