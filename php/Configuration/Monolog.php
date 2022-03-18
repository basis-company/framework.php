<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Http;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Monolog
{
    private string $name = 'default';

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function init(Container $container)
    {
        $container->share(LoggerInterface::class, function () use ($container) {
            $level = Logger::INFO;
            if (in_array(getenv('BASIS_ENVIRONMENT'), ['dev', 'testing'])) {
                $level = Logger::DEBUG;
            }

            $log = new Logger($this->name);
            $log->pushHandler(new class ($container) extends AbstractProcessingHandler {
                public function __construct(private Container $container)
                {
                    $this->subject = str_replace('-', '.', 'logs-' . gethostname());
                }
                protected function write(array $record): void
                {
                    $level = strtolower($record['level_name']);
                    unset($record['level_name']);
                    unset($record['level']);
                    $record['level'] = $level;

                    $span = $this->container->get(Tracer::class)->getActiveSpan();
                    $record['traceId'] = $span->getSpanContext()->getTraceId();

                    foreach (['context', 'extra'] as $key) {
                        if (array_key_exists($key, $record) && $record[$key] === []) {
                            unset($record[$key]);
                        }
                    }

                    unset($record['formatted']);
                    unset($record['datetime']);

                    $this->container->get(Client::class)->publish($this->subject, json_encode($record));
                }
            });

            return $log;
        });
    }
}
