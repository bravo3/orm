<?php
namespace Bravo3\ClassTools\Tests;

use Bravo3\ClassTools\Builder\ClassBuilder;
use Bravo3\ClassTools\Builder\Meta\ClassStruct;
use Bravo3\ClassTools\Tests\Resources\Extensible;

class ClassBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testStuff()
    {
        $foo = new ClassStruct('Bravo3\ClassTools\Resources\Foo', Extensible::class);

        $builder = new ClassBuilder();
        $code    = $builder->createClassCode($foo);

        echo "\n--\n".$code."--\n";
    }
}
