<?php

namespace Basis\Configuration;

use Amp\ByteStream;
use Basis\Container;
use Basis\Logger as BasisLogger;
use Psr\Log\LoggerInterface;

class Logger
{
    public function init(Container $container)
    {
        $container->share(LoggerInterface::class, function () {
            return new BasisLogger(ByteStream\getStdout());
        });
    }
}
