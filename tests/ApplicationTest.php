<?php

use Basis\Application;
use Basis\Converter;
use League\Container\Container;

class ApplicationTest extends TestSuite
{
    function testApplication()
    {
        $this->assertNotNull($this->app);
        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertSame($this->app, $this->app->get(Application::class));

        $container = $this->app->get(Container::class);
        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame($this->app, $container->get(Application::class));
    }

    function testConverter()
    {
        $source = [
            'nick' => 'test',
            'collection' => [
                ['id' => 1],
                ['id' => 2]
            ]
        ];

        $converter = $this->app->get(Converter::class);

        $object = $converter->toObject($source);

        $this->assertInternalType('object', $object);
        $this->assertSame($object->nick, 'test');
        $this->assertInternalType('array', $object->collection);
        $this->assertInternalType('object', $object->collection[0]);
        $this->assertSame(1, $object->collection[0]->id);
        $this->assertSame(2, $object->collection[1]->id);

        $array = $converter->toArray($object);
        $this->assertSame($source, $array);
    }
}