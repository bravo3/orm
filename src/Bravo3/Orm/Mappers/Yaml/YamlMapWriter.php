<?php
namespace Bravo3\Orm\Mappers\Yaml;

use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Sortable;
use Bravo3\Orm\Mappers\Portation\AbstractMapWriter;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Yaml\Yaml;

class YamlMapWriter extends AbstractMapWriter
{
    /**
     * @var string
     */
    protected $output_fn;

    /**
     * @var array
     */
    protected $main_buffer = [];

    /**
     * @var array
     */
    protected $sub_buffers = [];

    /**
     * @var int
     */
    protected $inline_level = 3;

    /**
     * @var int
     */
    protected $indent_spaces = 4;

    /**
     * @param string $output Primary output file
     */
    public function __construct($output = null)
    {
        $this->output_fn = $output;
    }

    /**
     * Write metadata for a given entity, using the input EntityManager that must be preset
     *
     * @param object      $entity   Entity with existing mapping data
     * @param string|null $resource File name to write data, if null a default or common value will be used
     * @return void
     */
    public function compileMetadataForEntity($entity, $resource = null)
    {
        $this->managerMustExist();

        $md = $this->manager->getMapper()->getEntityMetadata($entity);

        $data = [
            Schema::TABLE_NAME => $md->getTableName(),
            Schema::COLUMNS    => $this->compileColumns($md),
        ];

        if ($sortables = $this->compileSortIndices($md)) {
            $data[Schema::SORT_INDICES] = $sortables;
        }

        if ($indices = $this->compileStdIndices($md)) {
            $data[Schema::STD_INDICES] = $indices;
        }

        if ($resource) {
            $this->sub_buffers[$resource] = [$md->getClassName() => $data];
        } else {
            $this->main_buffer[$md->getClassName()] = $data;
        }
    }

    /**
     * Compile normal indices
     *
     * @param Entity $md
     * @return array
     */
    private function compileStdIndices(Entity $md)
    {
        $out     = [];
        $indices = $md->getIndices();

        foreach ($indices as $index) {
            $data = [];

            if ($index->getColumns()) {
                $data[Schema::INDEX_COLUMNS] = $index->getColumns();
            }

            if ($index->getMethods()) {
                $data[Schema::INDEX_METHODS] = $index->getMethods();
            }

            $out[$index->getName()] = $data;
        }

        return $out;
    }

    /**
     * Compile all sortable table indices
     *
     * @param Entity $md
     * @return array
     */
    private function compileSortIndices(Entity $md)
    {
        return $this->compileSortables($md->getSortables());
    }

    /**
     * Compile sortables into an array
     *
     * @param Sortable[] $sortables
     * @return array
     */
    private function compileSortables(array $sortables)
    {
        $out = [];

        foreach ($sortables as $index) {
            $data = [];

            if ($index->getColumn()) {
                $data[Schema::INDEX_COLUMN] = $index->getColumn();
            }

            $conditions = $this->compileConditions($index->getConditions());
            if ($conditions) {
                $data[Schema::INDEX_CONDITIONS] = $conditions;
            }

            $out[$index->getName()] = $data;
        }

        return $out;
    }

    /**
     * Compile conditions into an array
     *
     * @param \Bravo3\Orm\Mappers\Metadata\Condition[] $conditions
     * @return array
     */
    private function compileConditions(array $conditions)
    {
        $out = [];

        foreach ($conditions as $condition) {
            $data = [Schema::CONDITION_VALUE => $condition->getValue()];

            if ($condition->getColumn()) {
                $data[Schema::CONDITION_COLUMN] = $condition->getColumn();
            }

            if ($condition->getMethod()) {
                $data[Schema::CONDITION_METHOD] = $condition->getMethod();
            }

            if ($condition->getComparison() && ($condition->getComparison() != '=')) {
                $data[Schema::CONDITION_COMPARISON] = $condition->getComparison();
            }

            $out[] = $data;
        }

        return $out;
    }

    /**
     * Compiles all columns for an entity
     *
     * @param Entity $md
     * @return array
     */
    private function compileColumns(Entity $md)
    {
        $out     = [];
        $columns = $md->getColumns();

        foreach ($columns as $column) {
            $data = [
                Schema::COLUMN_TYPE => $column->getType()->value(),
            ];

            if ($column->isId()) {
                $data[Schema::COLUMN_ID] = true;
            }

            $default_getter = 'get'.Inflector::classify($column->getProperty());
            if ($column->getGetter() && ($column->getGetter() != $default_getter)) {
                $data[Schema::GETTER] = $column->getGetter();
            }

            $default_setter = 'set'.Inflector::classify($column->getProperty());
            if ($column->getSetter() && ($column->getSetter() != $default_setter)) {
                $data[Schema::SETTER] = $column->getSetter();
            }

            if ($column->getClassName()) {
                $data[Schema::COLUMN_CLASS] = $column->getClassName();
            }

            $out[$column->getName()] = $data;
        }

        $relationships = $md->getRelationships();

        foreach ($relationships as $relationship) {
            $data = [
                Schema::REL_ASSOCIATION => $relationship->getRelationshipType()->value(),
                Schema::REL_TARGET      => $relationship->getTarget(),
            ];

            $default_getter = 'get'.Inflector::classify($relationship->getName());
            if ($relationship->getGetter() && ($relationship->getGetter() != $default_getter)) {
                $data[Schema::GETTER] = $relationship->getGetter();
            }

            $default_setter = 'set'.Inflector::classify($relationship->getName());
            if ($relationship->getSetter() && ($relationship->getSetter() != $default_setter)) {
                $data[Schema::SETTER] = $relationship->getSetter();
            }

            if ($relationship->getInversedBy()) {
                $data[Schema::REL_INVERSED_BY] = $relationship->getInversedBy();
            }

            if ($relationship->getSortableBy()) {
                $data[Schema::SORT_INDICES] = $this->compileSortables($relationship->getSortableBy());
            }

            $out[$relationship->getName()] = $data;
        }

        return $out;
    }

    /**
     * Write YAML data to a file, overwriting its contents
     *
     * @param string $fn
     * @param array  $data
     * @return $this
     */
    private function writeYaml($fn, array $data)
    {
        $yaml = new Yaml();
        file_put_contents($fn, $yaml->dump($data, $this->getInlineLevel(), $this->getIndentSpaces()));

        return $this;
    }

    /**
     * Compile and write all processed metadata
     *
     * @return void
     */
    public function flush()
    {
        if ($this->output_fn && $this->main_buffer) {
            $this->writeYaml($this->output_fn, $this->main_buffer);
        }

        foreach ($this->sub_buffers as $fn => $buffer) {
            $this->writeYaml($fn, $buffer);
        }

        $this->purge();
    }

    /**
     * Purge the current buffer of unwritten content
     *
     * @return void
     */
    public function purge()
    {
        $this->main_buffer = [];
        $this->sub_buffers = [];
    }

    /**
     * Get the YAML indent level in which formatting is switched to inline
     *
     * @return int
     */
    public function getInlineLevel()
    {
        return $this->inline_level;
    }

    /**
     * Set the YAML indent level in which formatting is switched to inline
     *
     * @param int $inline_level
     * @return $this
     */
    public function setInlineLevel($inline_level)
    {
        $this->inline_level = (int)$inline_level;
        return $this;
    }

    /**
     * Get the number of spaces for YAML indenting
     *
     * @return int
     */
    public function getIndentSpaces()
    {
        return $this->indent_spaces;
    }

    /**
     * Set the number of spaces for YAML indenting
     *
     * @param int $indent_spaces
     * @return $this
     */
    public function setIndentSpaces($indent_spaces)
    {
        $this->indent_spaces = max(1, (int)$indent_spaces);
        return $this;
    }
}
