<?php

use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Drivers\Filesystem\Io\NativeIoDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Mappers\Portation\MapWriterInterface;
use Bravo3\Orm\Mappers\Yaml\YamlMapWriter;
use Bravo3\Orm\Services\EntityLocator;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Properties\Conf;
use Predis\Client;

require(__DIR__.'/../vendor/autoload.php');

Conf::init(__DIR__.'/config/', 'parameters.yml');

$redis = new Client(
    [
        'host'     => Conf::get('parameters.redis_host'),
        'port'     => Conf::get('parameters.redis_port'),
        'database' => Conf::get('parameters.redis_database')
    ]
);

$redis->flushdb();

// Solely for portation (reading metadata) - will never do anything
$em = EntityManager::build(new FilesystemDriver(new NativeIoDriver('/dev/null')), new AnnotationMapper());

$locator  = new EntityLocator($em);
$entities = $locator->locateEntities(__DIR__.'/Bravo3/Orm/Tests/Entities', 'Bravo3\Orm\Tests\Entities');

/** @var MapWriterInterface[] $porters */
$porters = [
    new YamlMapWriter(__DIR__.'/Bravo3/Orm/Tests/Resources/mappings.yml')
];

foreach ($porters as $porter) {
    $porter->setInputManager($em);

    foreach ($entities as $class_name) {
        $porter->compileMetadataForEntity($class_name);
    }
    $porter->flush();
}
