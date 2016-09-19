<?php

use Basis\Config;
use Example\BusinessLogic;
use League\Container\Container;

class ProviderTest extends TestSuite
{
    function test()
    {
        $container = $this->app->get(Container::class);
        $this->assertTrue($container->has(BusinessLogic::class));
    }
}