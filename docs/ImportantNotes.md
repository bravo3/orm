Important Notes
===============

Serialisation
-------------
* You can use only primitive data-types (int, decimal, string, bool) and \DateTime ("datetime") objects for columns
* ID columns cannot be 'datetime' fields
* Boolean fields are converted to integers when used in an index

Lazy-loading
------------
* Primitive entity data is not deserialised until you first access it
* All primitive data is hydrated when any other primitive property is first accessed
* Relationships are not fetched from the database until you first read it
* Different relationships are not hydrated together, they will be individually fetched when each relationship is accessed
* List relationships (eg One-To-Many) will be fully hydrated when the list is accessed

Relationship Columns
--------------------
* Relationships are not columns, you cannot mix a @Column annotation with a @OneToMany (or similar) annotation
* Relationships are not serialised in the main entity, they are handled by auxiliary indices
* You cannot reference relationships in entity's indices 
* Adding inverse relationships to existing relationships with data will not hydrate the inverse index, you will have a desychronised index

Multi-Column IDs
----------------
* ID's and indices are concatenated in the order they appear
* ID/index concatenation is delimited by a period (.)
* e.g. "id1.id2"

Relationship Getters and Setters
--------------------------------
For the sake of optimisation, changes to the relationships are monitored so that no additional work needs to be
performed when persisting the entity. As such, it is very important to call the getter in order to lazily hydrate the
relationship, and call the setter in order to mark the relationship as modified.

Example:

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
     * Clear the articles list
     *
     * @return $this
     */
    public function clearArticles()
    {
        $this->setArticles([]);
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
    
The ListManager class is provided to make the addition and removal of entities simple, this class will call the getter
and setter functions, ensuring the entity is properly tracked.

Example of functions that will create issues:

    /**
     * Add an article to the category
     *
     * WARNING: does not call the setter, changes will not be noticed!
     *
     * @param Article $article
     * @return $this
     */
    public function addArticle(Article $article)
    {
        $this->articles[] = $article;
        return $this;
    }
    
    /**
     * Some some stuff, returning articles
     *
     * @return Article[]
     */
    public function doStuff()
    {
        // insert stuff here
        
        // WARNING: $this->articles might not be hydrated!
        return $this->articles;
        
        // Should be:
        // return $this->getArticles();
    }
