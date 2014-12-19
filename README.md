Content Manager
===============
This library is a content abstraction layer replacing typical database interaction. It can push and pull content to
API servers, local databases or any other custom implementation.

There are 2 primary components to this library, a 'content manager' and a 'furniture manager'. These are split into
two managers to allow for different sources of regular content (eg articles, images) and site furniture
(eg menus, modules).


See also:

* [Taxonomy](docs/Taxonomy.md) - understanding the taxonomy system
* [Foreign Key Relationships](docs/ForeignKeyRelationships.md) - a look into how relationships are handled in no-SQL databases
* [Events](docs/Events.md) - how to tap into the event dispatcher
* [Data Serialisation](docs/Serialisation.md)
* [Entity Mappings](docs/EntityMappings.md) - a good place to start when looking to extend


Drivers
=======

News API Driver
---------------
This driver allows the News content API (CAPI) to be the authoritative data-source for content. Site furniture is not
supported by CAPI *(currently)*.

Redis Driver
------------
The Redis driver allows you to use a Redis server for content and furniture. In addition to being a great authoritative
driver, this would be an ideal caching driver. There is no performance gain using it as both, however.

To use the Redis driver, you will need to add Predis to your `composer.json`:

    "require": {
        "predis/predis": "~0.8.7"
    }

Caching Layers
==============
This library allows for a caching layer between the client and the data-source. This is achieved by defining a second
driver to act as the cache driver. Ideally, this would be a Redis driver or similar.

TTL & Invalidation
------------------
Coming.

Example Usage
=============

    // Authoritative driver - CAPI
    $driver = new CapiContentDriver([
        'host' => '10.1.0.1',
        'key' => '...',
        'secret' => '...,
    ]);

    // Caching driver - Redis using DB #8
    $cache_driver = new RedisContentDriver([
        'host' => '10.2.0.1',
        'port' => '6379',
        'database' => 8,
    ]);

    // Our content manager, can push and pull content
    $content_manager = new ContentManager($driver, $cache_driver);

    // Get some content
    $article = $content_manager->getArticleById(123);
    echo $article->getTitle()."\n";

    // Update the content
    $article->setTitle("Test Title");
    $content_manager->persist($article);

Change Log
==========

1.1.1
-----
* Added entity mappers

1.1.0
-----
* Added an observer pattern to the drivers & CM
* Moved the image namespace just to be a pain
* Added Person entity
* Added create/modify timestamps to all entities
* Added serialisation format code to all entities so that new methods or compression can be added

1.0.0
-----

* Improved the key structure in Redis databases
* Removed the Furniture manager
* Removed direct per-entity functionality from the content manager
* Added in a repository model for retrieving entity features, such as child hydration
* Added a schema for entity retrievals
* It is now safe to use the same ID's over multiple sites

0.3.0
-----

* Data serialisation model changed to JSON from PHP serialisation

0.2.0
-----

* Moved to open taxonomy model, you can now add new taxonomy classes by implementing the TaxonomyInterface
    * This has resulted in function name changes to the hydration functions on the content manager
* Entities may now a canonical category, canonical collection, a set of categories and a set of collections as the
  default taxonomy model
* Setting a canonical category will no longer add to the categories list - secondary categories are independent to the
  canonical category and must be handled appropriately
* Recursion now restricted to article hydration only
* Recursive article hydration will work on any taxonomy type
* Circular taxonomy references will throw a CircularReferenceException during hydration
* Added image hydration for galleries
