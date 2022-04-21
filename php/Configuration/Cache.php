<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Cache
{
    public function init(Container $container)
    {
        $container->share(ArrayAdapter::class, function () {
            return new ArrayAdapter();
        });

        $container->share(FilesystemAdapter::class, function () {
            return new FilesystemAdapter();
        });

        if (getenv('BASIS_ENVIRONMENT') === 'testing') {
            $container->share(AdapterInterface::class, ArrayAdapter::class);
        } else {
            $container->share(AdapterInterface::class, function () use ($container) {
                return new ChainAdapter([
                    $container->get(ArrayAdapter::class),
                    $container->get(FilesystemAdapter::class),
                ]);
            });
        }
    }
}
