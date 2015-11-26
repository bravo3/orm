<?php
namespace Bravo3\Orm\Services;

class EntityLocator
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @var callable
     */
    protected $normaliser;

    /**
     * @param EntityManager $entity_manager
     */
    public function __construct(EntityManager $entity_manager = null)
    {
        $this->entity_manager = $entity_manager;
        $this->setNormaliser(function ($input) {
            $pos = strrpos($input, '.');
            if ($pos === false) {
                return $input;
            } else {
                return substr($input, 0, $pos);
            }
        });
    }

    /**
     * Gather all entities in a given directory
     *
     * Assumes PSR-0/4 compliant entity class names, and an appropriate auto-loader is installed.
     *
     * @param string $dir       Directory to scan
     * @param string $namespace PSR base namespace for the directory
     * @param string $regex     Filter for file matching
     * @return string[]
     */
    public function locateEntities($dir, $namespace, $regex = '/^.+\.php$/i')
    {
        $dir = str_replace('\\', '/', $dir);
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        $base_len = strlen($dir);

        $namespace = str_replace('/', '\\', $namespace);
        if (substr($namespace, -1) != '\\') {
            $namespace .= '\\';
        }

        $normaliser = $this->getNormaliser();
        $iterator   = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)),
            $regex,
            \RecursiveRegexIterator::GET_MATCH
        );


        $out = [];
        foreach ($iterator as $fn => $data) {
            $suffix = str_replace('/', '\\', substr($fn, $base_len));
            $class  = $normaliser($namespace.$suffix);

            if (!class_exists($class)) {
                continue;
            }

            if ($this->entity_manager) {
                try {
                    $this->entity_manager->getMapper()->getEntityMetadata($class);
                } catch (\Exception $e) {
                    continue;
                }
            }

            $out[] = $class;

        }

        return $out;
    }

    /**
     * Get class normaliser
     *
     * By default this will return a callback that will simply strip the file extension from the input. If you have a
     * more complex filename structure you can account for it using the #setNormaliser() function (see docblock).
     * However be aware that you're probably not PSR compliant if you need to do this.
     *
     * @return callable
     */
    public function getNormaliser()
    {
        return $this->normaliser;
    }

    /**
     * Set class normaliser
     *
     * This must be a callback that takes 1 argument and will return a string. Its purpose is to convert file names
     * into class names, for example, "MyEntity.php" should return "MyEntity". It will only ever operate on the final
     * segment of a namespace.
     *
     * @param callable $normaliser
     * @return $this
     */
    public function setNormaliser(callable $normaliser)
    {
        $this->normaliser = $normaliser;
        return $this;
    }

    /**
     * Get the entity manager currently used for validation
     *
     * @return EntityManager|null
     */
    public function getEntityManager()
    {
        return $this->entity_manager;
    }

    /**
     * Set the entity manager, if null no entity validation will occur
     *
     * @param EntityManager $entity_manager
     * @return $this
     */
    public function setEntityManager(EntityManager $entity_manager = null)
    {
        $this->entity_manager = $entity_manager;
        return $this;
    }
}
