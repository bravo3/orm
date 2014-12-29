<?php
namespace Bravo3\Orm\Serialisers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Enum\FieldType;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Mappers\Metadata\Column;
use Bravo3\Orm\Mappers\Metadata\Entity;

class JsonSerialiser implements SerialiserInterface
{
    /**
     * Serialiser header code, will be prefixed to documents
     */
    const SERIALISER_CODE = 'JSON';

    /**
     * JSON nesting level, since only primitive data types should be encoded, there should never be any nesting, thus
     * only 1 level from the root (2) is required
     */
    const ENCODE_DEPTH = 2;

    /**
     * json_encode options, see: http://php.net/manual/en/json.constants.php
     */
    const ENCODE_OPTIONS = 0;

    /**
     * Get a unique 4-byte ANSI code for this serialiser, used as the header/metadata for the stored document
     *
     * @return string
     */
    public function getSerialiserCode()
    {
        return self::SERIALISER_CODE;
    }

    /**
     * Serialise the entity
     *
     * @param Entity $metadata
     * @param object $entity
     * @return SerialisedData
     */
    public function serialise(Entity $metadata, $entity)
    {
        $data   = new \stdClass();
        $reader = new Reader($metadata, $entity);

        foreach ($metadata->getColumns() as $column) {
            $this->assignValue($data, $column, $reader->getPropertyValue($column->getProperty()));
        }

        return new SerialisedData(self::SERIALISER_CODE, json_encode($data, self::ENCODE_OPTIONS, self::ENCODE_DEPTH));
    }

    /**
     * Assign a type-casted value to the data object
     *
     * Fields of unknown types will not be added to the object.
     *
     * @param \stdClass $data
     * @param Column    $column
     * @param mixed     $value
     */
    private function assignValue(\stdClass $data, Column $column, $value)
    {
        $field_name = $column->getName();

        switch ($column->getType()) {
            default:
                break;
            case FieldType::DATETIME():
                $data->$field_name = $this->serialiseDateTime($value);
                break;
            case FieldType::INT():
                $data->$field_name = (int)$value;
                break;
            case FieldType::STRING():
                $data->$field_name = (string)$value;
                break;
            case FieldType::DECIMAL():
                $data->$field_name = (float)$value;
                break;
            case FieldType::BOOL():
                $data->$field_name = (bool)$value;
                break;
        }
    }

    /**
     * Format the DateTime object appropritately for raw output
     *
     * @param \DateTime $value
     * @return string
     */
    private function serialiseDateTime(\DateTime $value)
    {
        return $value->format('c');
    }

    /**
     * Deserialise the entity
     *
     * @param Entity         $metadata Metadata object to match the entity
     * @param SerialisedData $data     Data to deserialise
     * @param object         $entity   Entity to hydrate
     */
    public function deserialise(Entity $metadata, SerialisedData $data, $entity)
    {
        // Using $assoc = true is ~ 10-20% quicker on PHP 5.3
        // Source: http://stackoverflow.com/questions/8498114/should-i-use-an-associative-array-or-an-object
        $raw = json_decode($data->getData(), true, self::ENCODE_DEPTH);

        foreach ($metadata->getColumns() as $column) {
            $setter = $column->getSetter();
            $field  = $column->getName();
            $value  = $raw[$field];

            switch ($column->getType()) {
                default:
                    break;
                case FieldType::DATETIME():
                    $entity->$setter(new \DateTime($value));
                    break;
                case FieldType::INT():
                    $entity->$setter((int)$value);
                    break;
                case FieldType::STRING():
                    $entity->$setter((string)$value);
                    break;
                case FieldType::DECIMAL():
                    $entity->$setter((float)$value);
                    break;
                case FieldType::BOOL():
                    $entity->$setter((bool)$value);
                    break;
            }
        }
    }
}
