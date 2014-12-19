<?php
namespace Bravo3\Orm\Drivers;

interface DriverInterface
{

    public function persist($entity);

    public function delete($entity);

    public function retrieve($name, $query);

    public function flush();
}
