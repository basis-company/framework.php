<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Http;
use Basis\Nats\Client;
use Basis\Telemetry\Tracing\Tracer;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
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
            $log->pushHandler(new class ($container) extends StreamHandler {
                public function __construct(private Container $container)
                {
                    parent::__construct('php://stdout');
                }
                protected function streamWrite($stream, array $record): void
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

                    if (array_key_exists('context', $record)) {
                        foreach ($record['context'] as $key => $value) {
                            if (is_string($value) && strlen($value) > 120) {
                                $record['context'][$key] = substr($value, 0, 120) + '...';
                            }
                        }
                    }

                    unset($record['formatted']);
                    unset($record['datetime']);
                    fwrite($stream, json_encode($record) . PHP_EOL);
                }
            });

            return $log;
        });
    }
}
