<?php

use Basis\Config;
use League\Container\Container;
use LinkORB\Component\Etcd\Client;

class ProviderTest extends TestSuite
{
    function test()
    {
        $container = $this->app->get(Container::class);
        $this->assertTrue($container->has(BusinessLogic::class));
        $this->assertSame($container->get(BusinessLogic::class), $container->get(BusinessLogic::class));
        $this->assertSame($container->get(Client::class), $container->get(Client::class));
    }
}