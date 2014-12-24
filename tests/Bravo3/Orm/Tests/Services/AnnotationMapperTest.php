<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Tests\Entities\Article;
use Bravo3\Orm\Tests\Entities\UserGroup;

class AnnotationMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testStuff()
    {
        $mapper = new AnnotationMapper();

        $article      = new Article();
        $article_meta = $mapper->getEntityMetadata($article);
        $this->assertEquals("articles", $article_meta->getTableName());

        $user_group      = new UserGroup();
        $user_group_meta = $mapper->getEntityMetadata($user_group);
        $this->assertEquals("user_group", $user_group_meta->getTableName());
    }
}
