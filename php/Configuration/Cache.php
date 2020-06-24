<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class Cache
{
    public function init(Container $container)
    {
        $container->share(AdapterInterface::class, PhpFilesAdapter::class);

        $container->share(PhpFilesAdapter::class, function () {
            return new PhpFilesAdapter('', 0, 'cache');
        });
    }
}
