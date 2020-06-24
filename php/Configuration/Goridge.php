<?php

namespace Basis\Configuration;

use Spiral\Goridge\RelayInterface;
use Spiral\Goridge\SocketRelay;
use Basis\Container;

class Goridge
{
    public function init(Container $container)
    {
        $container->share(RelayInterface::class, SocketRelay::class);
        $container->share(SocketRelay::class, function () {
            return new SocketRelay('0.0.0.0', 6001);
        });
    }
}
