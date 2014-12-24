<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Services\AnnotationMapper;
use Bravo3\Orm\Tests\Entities\Article;
use Bravo3\Orm\Tests\Entities\UserGroup;

class AnnotationEntityReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testStuff()
    {
        $article        = new Article();
        $article_mapper = new AnnotationMapper($article);
        $this->assertEquals("articles", $article_mapper->getTableName());

        $user_group        = new UserGroup();
        $user_group_mapper = new AnnotationMapper($user_group);
        $this->assertEquals("user_group", $user_group_mapper->getTableName());

    }
}
