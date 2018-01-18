<?php

namespace Test;

use Basis\Config;
use BusinessLogic;
use League\Container\Container;
use Basis\Test;

class ProviderTest extends Test
{
    public function test()
    {
        $container = $this->app->get(Container::class);
        $this->assertTrue($container->has(BusinessLogic::class));
        $this->assertSame($container->get(BusinessLogic::class), $container->get(BusinessLogic::class));
    }
}
