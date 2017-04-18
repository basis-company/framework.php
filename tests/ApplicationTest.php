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
}