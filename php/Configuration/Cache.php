<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class Cache
{
    public function init(Container $container)
    {
        $container->share(ArrayAdapter::class, function () {
            return new ArrayAdapter();
        });

        $container->share(PhpFilesAdapter::class, function () {
            return new PhpFilesAdapter();
        });

        if (getenv('BASIS_ENVIRONMENT') === 'testing') {
            $container->share(AdapterInterface::class, ArrayAdapter::class);
        } else {
            $container->share(AdapterInterface::class, PhpFilesAdapter::class);
        }
    }
}
