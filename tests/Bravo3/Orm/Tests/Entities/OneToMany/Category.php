<?php
namespace Bravo3\Orm\Tests\Entities\OneToMany;

use Bravo3\Orm\Annotations as Orm;
use Bravo3\Orm\Services\ListManager;

/**
 * @Orm\Entity()
 */
class Category
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
    protected $name;

    /**
     * @var Article[]
     * @Orm\OneToMany(
     *      target="Bravo3\Orm\Tests\Entities\OneToMany\Article",
     *      inversed_by="canonical_category",
     *      sortable_by={"title", "sort_date"}
     * )
     */
    protected $articles;

    /**
     * Get Id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Id
     *
     * @param mixed $id
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
     * Get Articles
     *
     * @return Article[]
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * Set Articles
     *
     * @param Article[] $articles
     * @return $this
     */
    public function setArticles(array $articles)
    {
        $this->articles = $articles;
        return $this;
    }

    /**
     * Add an article to the category
     *
     * @param Article $article
     * @return $this
     */
    public function addArticle(Article $article)
    {
        ListManager::add($this, 'articles', $article);
        return $this;
    }

    /**
     * Remove an article from the category
     *
     * @param Article $article
     * @return $this
     */
    public function removeArticle(Article $article)
    {
        ListManager::remove($this, 'articles', $article, ['getId']);
        return $this;
    }
}
