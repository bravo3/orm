<?php
namespace Bravo3\Orm\Serialisers;

interface SerialiserInterface
{
    public function getId();

    public function serialise($entity);

    public function deserialise($class_name, $data);
}
