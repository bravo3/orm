<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\EntityManager;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Tests\Entities\Article;

class EntityManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testIo()
    {
        $driver = $this->getDriver();
        $mapper = new AnnotationMapper();
        $em     = new EntityManager($driver, $mapper);

        $article = new Article();
        $article->setId(123)->setTitle('Test Article')->setBody("lorem ipsum");

        $em->persist($article);
        $em->flush();

        /** @var Article $new_article */
        $new_article = $em->retrieve('Bravo3\Orm\Tests\Entities\Article', 123);

        $this->assertEquals($article->getId(), $new_article->getId());
        $this->assertEquals($article->getTitle(), $new_article->getTitle());
        $this->assertEquals($article->getBody(), $new_article->getBody());
    }

    protected function getDriver()
    {
        return new RedisDriver(['host' => 'localhost', 'database' => 2]);
    }
}
