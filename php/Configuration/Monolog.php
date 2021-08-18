<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Http;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\ProcessorInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Basis\Telemetry\Tracing\Tracer;

class Monolog
{
    public string $name = 'http';

    public function init(Container $container)
    {
        $container->share(LoggerInterface::class, function () use ($container) {
            $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true, true);

            $level = Logger::INFO;
            if (getenv('BASIS_ENVIRONMENT') == 'dev') {
                $level = Logger::DEBUG;
            }

            $handler = new StreamHandler("var/log", $level);
            $handler->setFormatter($formatter);

            $handler->pushProcessor(new class ($container) implements ProcessorInterface {
                public function __construct(private Container $container)
                {
                }
                public function __invoke(array $record)
                {
                    $level = strtolower($record['level_name']);
                    unset($record['level_name']);
                    unset($record['level']);
                    unset($record['datetime']);
                    $span = $this->container->get(Tracer::class)->getActiveSpan();
                    $record['level'] = $level;
                    $record['traceId'] = $span->getSpanContext()->getTraceId();
                    return $record;
                }
            });

            $log = new Logger($this->name);
            $log->pushHandler($handler);

            return $log;
        });
    }
}
