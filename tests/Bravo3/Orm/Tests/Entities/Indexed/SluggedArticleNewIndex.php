<?php
namespace Bravo3\Orm\Tests\Entities\Indexed;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\Index;

/**
 * @Entity(indices={
 *      @Index(name="category__slug", columns={"category", "slug"}),
 *      @Index(name="category__name", columns={"category", "name"})
 * },table="slugged_article")
 */
class SluggedArticleNewIndex
{
    /**
     * @var int
     * @Id
     * @Column(type="int")
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $name;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $slug;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $category;

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
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get Slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set Slug
     *
     * @param string $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get Category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set Category
     *
     * @param string $category
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }
}
