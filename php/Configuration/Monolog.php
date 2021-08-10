<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Http;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\ProcessorInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Monolog
{
    public string $name = 'http';

    public function init(Container $container)
    {
        $container->share(LoggerInterface::class, function () use ($container) {
            $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, true);

            $handler = new StreamHandler("/var/application.log", Logger::INFO);
            $handler->setFormatter($formatter);

            $handler->pushProcessor(new class implements ProcessorInterface {
                public function __invoke(array $record)
                {
                    unset($record['level']);
                    unset($record['datetime']);
                    return $record;
                }
            });

            $log = new Logger($this->name);
            $log->pushHandler($handler);

            return $log;
        });
    }
}
