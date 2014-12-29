<?php
namespace Bravo3\Orm\Tests\Entities\OneToMany;

use Bravo3\Orm\Annotations as Orm;

/**
 * @Orm\Entity()
 */
class Article
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
     * @Orm\ManyToOne(target="Bravo3\Orm\Tests\Entities\OneToMany\Category", inversed_by="articles")
     */
    protected $canonical_category;

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
     * Get CanonicalCategory
     *
     * @return Category
     */
    public function getCanonicalCategory()
    {
        return $this->canonical_category;
    }

    /**
     * Set CanonicalCategory
     *
     * @param Category $canonical_category
     * @return $this
     */
    public function setCanonicalCategory(Category $canonical_category)
    {
        $this->canonical_category = $canonical_category;
        return $this;
    }
}
