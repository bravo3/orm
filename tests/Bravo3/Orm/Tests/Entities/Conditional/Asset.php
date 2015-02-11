<?php
namespace Bravo3\Orm\Tests\Entities\Conditional;

use Bravo3\Orm\Annotations as Orm;
use Bravo3\Orm\Traits\CreateModifyInterface;

/**
 * @Orm\Entity()
 */
class Asset implements CreateModifyInterface
{
    /**
     * @var int
     * @Orm\Id
     * @Orm\Column(type="int")
     */
    protected $id;

    /**
     * @var string
     * @Orm\Column(type="string")
     */
    protected $title;

    /**
     * @var Category
     * @Orm\ManyToOne(target="Bravo3\Orm\Tests\Entities\Conditional\Category", inversed_by="assets")
     */
    protected $category;

    /**
     * @var \DateTime
     * @Orm\Column(type="datetime")
     */
    private $time_created;

    /**
     * @var \DateTime
     * @Orm\Column(type="datetime")
     */
    private $last_modified;

    /**
     * @var bool
     * @Orm\Column(type="bool")
     */
    protected $published;

    /**
     * Get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set Title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get Category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set Category
     *
     * @param Category $category
     * @return $this
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get the time the entity was created
     *
     * @return \DateTime
     */
    public function getTimeCreated()
    {
        return $this->time_created;
    }

    /**
     * Set the time the entity was created
     *
     * @param \DateTime $time_created
     * @return $this
     */
    public function setTimeCreated(\DateTime $time_created)
    {
        $this->time_created = $time_created;
        return $this;
    }

    /**
     * Get the time the entity was last modified
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set the time the entity was last modified
     *
     * @param \DateTime $last_modified
     * @return $this
     */
    public function setLastModified(\DateTime $last_modified)
    {
        $this->last_modified = $last_modified;
        return $this;
    }

    /**
     * Get Published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set Published
     *
     * @param boolean $published
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = $published;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublished()
    {
        return $this->published;
    }
}
