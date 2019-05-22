<?php

namespace Basis\Provider;

use Basis\Config;
use Basis\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Tarantool\Client\Client;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Entity;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\Sequence;
use Tarantool\Mapper\Plugin\Spy;
use Tarantool\Mapper\Plugin\Temporal;
use Tarantool\Mapper\Schema;

class TarantoolProvider extends AbstractServiceProvider
{
    protected $provides = [
        Bootstrap::class,
        Client::class,
        Mapper::class,
        Schema::class,
        Spy::class,
        Temporal::class,
    ];

    public function register()
    {
        $this->container->share(Bootstrap::class, function () {
            return $this->container->get(Mapper::class)->getBootstrap();
        });

        $this->getContainer()->share(Client::class, function () {
            $config = $this->getContainer()->get(Config::class);
            $params = [
                'uri' => $config['tarantool.connection'],
            ];
            $client = Client::fromOptions(array_merge($config['tarantool.params'], $params));
            $client->evaluate("box.session.su('admin')");
            return $client;
        });

        $this->getContainer()->share(Mapper::class, function () {
            $mapper = new Mapper($this->getContainer()->get(Client::class));
            $filesystem = $this->getContainer()->get(Filesystem::class);

            $mapperCache = $filesystem->getPath('.cache/mapper-meta.php');
            if (file_exists($mapperCache)) {
                $meta = include $mapperCache;
                $mapper->setMeta($meta);
            }

            $annotation = $mapper->getPlugin(Annotation::class);

            foreach ($filesystem->listClasses('Entity') as $class) {
                $annotation->register($class);
            }
            foreach ($filesystem->listClasses('Repository') as $class) {
                $annotation->register($class);
            }

            $mapper->getPlugin(Sequence::class);
            $mapper->getPlugin(Spy::class);

            $mapper->getPlugin(Temporal::class)
                ->getAggregator()
                ->setReferenceAggregation(false);

            $mapper->application = $this->getContainer();

            $mapper->getPlugin(new class($mapper) extends Plugin {
                public function afterInstantiate(Entity $entity) : Entity
                {
                    $entity->app = $this->mapper->application;
                    return $entity;
                }
            });

            return $mapper;
        });

        $this->getContainer()->share(Schema::class, function () {
            return $this->getContainer()->get(Mapper::class)->getSchema();
        });

        $this->getContainer()->share(Spy::class, function () {
            return $this->getContainer()->get(Mapper::class)->getPlugin(Spy::class);
        });

        $this->getContainer()->share(Temporal::class, function () {
            return $this->getContainer()->get(Mapper::class)->getPlugin(Temporal::class);
        });
    }
}
