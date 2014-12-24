<?php
namespace Bravo3\Orm\Serialisers;

class SerialiserMap
{
    /**
     * @var SerialiserInterface[]
     */
    protected $serialisers = [];

    /**
     * @var string
     */
    protected $default_serialiser;

    public function addSerialiser(SerialiserInterface $serialiser)
    {
        $this->serialisers[$serialiser->getId()] = $serialiser;

        if (count($this->serialisers) == 1) {
            $this->default_serialiser = $serialiser->getId();
        }
    }

    public function getSerialiser($id)
    {
        return $this->serialisers[$id];
    }

    public function removeSerialiser($id)
    {
        unset($this->serialisers[$id]);
    }

    public function getDefaultSerialiser()
    {
        return $this->getSerialiser($this->default_serialiser);
    }

    public function setDefaultSerialiserId($id)
    {
        $this->default_serialiser = $id;
    }

    public function getDefaultSerialiserId()
    {
        return $this->default_serialiser;
    }
}