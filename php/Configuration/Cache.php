<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

class Cache
{
    public function init(Container $container)
    {
        $container->share(AdapterInterface::class, ApcuAdapter::class);

        $container->share(ApcuAdapter::class, function () {
            return new ApcuAdapter();
        });
    }
}
