<?php

namespace Test;

use Basis\Application;
use Basis\Converter;
use Basis\Test;
use League\Container\Container;

class ApplicationTest extends Test
{
    public function testApplication()
    {
        $this->assertNotNull($this->app);
        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertSame($this->app, $this->app->get(Application::class));

        $container = $this->app->get(Container::class);
        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame($this->app, $container->get(Application::class));
    }

    public function testAssets()
    {
        $assets = get_object_vars($this->app->dispatch('module.assets'));
        $this->assertArrayHasKey('hash', $assets);
        $this->assertArrayHasKey('js', $assets);
        $this->assertArrayHasKey('test.js', get_object_vars($assets['js']));
        $this->assertArrayHasKey('styl', $assets);
        $this->assertArrayHasKey('file.styl', get_object_vars($assets['styl']));

    }
}
