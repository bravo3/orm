<?php

use Bravo3\Properties\Conf;
use Predis\Client;

require(__DIR__.'/../vendor/autoload.php');

Conf::init(__DIR__.'/config/', 'parameters.yml');

$redis = new Client(
    [
        'host' => Conf::get('parameters.redis_host'),
        'port' => Conf::get('parameters.redis_port'),
        'database' => Conf::get('parameters.redis_database')
    ]
);

$redis->flushdb();

