<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Basis\Configuration\Mapper as MapperConfiguration;
use Basis\Configuration\Tarantool as TarantoolConfiguration;
use Basis\Middleware\TarantoolRetryMiddleware;
use Basis\Middleware\TarantoolProcMiddleware;
use Basis\Toolkit;
use Basis\Dispatcher;
use Exception;
use Tarantool\Client\Client;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Plugin\Temporal;
use Tarantool\Mapper\Pool as TarantoolPool;

class Pool
{
    use Toolkit;

    public function init()
    {
        $this->getContainer()->share(TarantoolPool::class, function () {
            $pool = new TarantoolPool();

            $pool->registerResolver(function ($name) {

                if ($name == $this->app->getName()) {
                    return $this->get(Mapper::class);
                }

                $container = $this->get(Container::class);
                if (!$container->has("$name-client")) {
                    $address = $this->dispatch('resolve.address', [
                        'name' => $name . '-db',
                    ]);
                    $options = [
                        'uri' => 'tcp://' . $address->host . ':3301',
                        'persistent' => getenv('TARANTOOL_CLIENT_PERSISTENT_CONNECTION') !== 'false',
                    ];

                    $client = Client::fromOptions($options)
                        ->withMiddleware(
                            $this->get(TarantoolRetryMiddleware::class),
                            new TarantoolProcMiddleware($name, $this->get(Dispatcher::class))
                        );

                    try {
                        $client->evaluate("box.session.su('admin')");
                    } catch (Exception $e) {
                    }
                    $container->share("$name-client", $client);
                }

                $mapper = new Mapper($container->get("$name-client"));
                $mapper->getPlugin(Sequence::class);
                $mapper->getPlugin(Spy::class);
                $mapper->getPlugin(Temporal::class)
                    ->getAggregator()
                    ->setReferenceAggregation(false);

                $mapper->service = $name;
                $mapper->serviceName = $name;

                return $mapper;
            });
            return $pool;
        });
    }
}
