<?php

namespace Basis\Providers;

use Basis\Application;
use Basis\Config;
use Basis\Fiber;
use Basis\Queue;
use Basis\Service as BasisService;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

class Service extends AbstractServiceProvider
{
    protected $provides = [
        Queue::class,
        BasisService::class,
    ];

    public function register()
    {
        $this->container->share(Queue::class, function() {
            $config = $this->container->get(Config::class);
            $connection = new StreamConnection('tcp://'.$config['queue.host'].':'.$config['queue.port'], [
                'socket_timeout' => $config['queue.socket_timeout'] ?: 60,
                'connect_timeout' => $config['queue.connect_timeout'] ?: 60,
            ]);
            return new Queue($connection, new PurePacker());
        });

        $this->container->share(BasisService::class, function() {
            $app = $this->container->get(Application::class);
            $config = $this->container->get(Config::class);
            $queue = $this->container->get(Queue::class);
            $tube = $config['service.tube'];
            return new BasisService($app, $queue, $tube);
        });
    }
}