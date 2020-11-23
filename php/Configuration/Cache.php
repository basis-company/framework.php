<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class Cache
{
    public function init(Container $container)
    {
        $container->share(ApcuAdapter::class, function () {
            return new ApcuAdapter();
        });

        $container->share(ArrayAdapter::class, function () {
            return new ArrayAdapter();
        });

        if (in_array('apcu', get_loaded_extensions())) {
            $container->share(AdapterInterface::class, ApcuAdapter::class);
        } else {
            $container->share(AdapterInterface::class, ArrayAdapter::class);
        }
    }
}
