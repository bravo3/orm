<?php
namespace Bravo3\Orm\Tests\Entities\Conditional;

use Bravo3\Orm\Annotations as Orm;
use Bravo3\Orm\Annotations\Condition;
use Bravo3\Orm\Annotations\Sortable;
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
     *      target="Bravo3\Orm\Tests\Entities\Conditional\Article",
     *      inversed_by="category",
     *      sortable_by={
     *          @Sortable(column="last_modified", conditions={
     *              @Condition(column="published", value=true),
     *              @Condition(column="id", value=50, comparison=">")
     *          }), "id"
     *      })
     */
    protected $articles;

    /**
     * @var Asset[]
     * @Orm\OneToMany(
     *      target="Bravo3\Orm\Tests\Entities\Conditional\Asset",
     *      inversed_by="category",
     *      sortable_by={
     *          @Sortable(column="last_modified", conditions={
     *              @Condition(method="isPublished", value=true),
     *              @Condition(column="id", value=50, comparison=">")
     *          }), "id"
     *      })
     */
    protected $assets;

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

    /**
     * Get Assets
     *
     * @return Asset[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Set Assets
     *
     * @param Asset[] $assets
     * @return $this
     */
    public function setAssets(array $assets)
    {
        $this->assets = $assets;
        return $this;
    }

    /**
     * Add an asset to the category
     *
     * @param Asset $asset
     * @return $this
     */
    public function addAsset(Asset $asset)
    {
        ListManager::add($this, 'assets', $asset);
        return $this;
    }

    /**
     * Remove an asset from the category
     *
     * @param Asset $asset
     * @return $this
     */
    public function removeAsset(Asset $asset)
    {
        ListManager::remove($this, 'assets', $asset, ['getId']);
        return $this;
    }
}
