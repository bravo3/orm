<?php
namespace Bravo3\Orm\Drivers\Common;

/**
 * Serialised data with serialisation metadata
 */
class SerialisedData
{
    /**
     * @var string
     */
    protected $serialisation_code;

    /**
     * @var string
     */
    protected $data;

    public function __construct($serialisation_code = null, $data = null)
    {
        $this->serialisation_code = $serialisation_code;
        $this->data               = $data;
    }

    /**
     * Get SerialisationCode
     *
     * @return string
     */
    public function getSerialisationCode()
    {
        return $this->serialisation_code;
    }

    /**
     * Set SerialisationCode
     *
     * @param string $serialisation_code
     * @return $this
     */
    public function setSerialisationCode($serialisation_code)
    {
        $this->serialisation_code = $serialisation_code;
        return $this;
    }

    /**
     * Get Data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set Data
     *
     * @param string $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
