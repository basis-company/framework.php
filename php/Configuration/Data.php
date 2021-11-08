<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Data\Master;
use Basis\Data\Wrapper;
use Basis\Dispatcher;

class Data
{
    public function init(Container $container)
    {
        $container->share(Master::class, function () use ($container) {
            return new Master($container->get(Dispatcher::class));
        });

        $container->share(Wrapper::class, function () use ($container) {
            return $container->get(Master::class)->getWrapper();
        });
    }
}
