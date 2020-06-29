<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Logger as BasisLogger;
use Psr\Log\LoggerInterface;

class Logger
{
    public function init(Container $container)
    {
        $container->share(LoggerInterface::class, BasisLogger::class);
    }
}
