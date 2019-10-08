<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;
use OpenTelemetry\Tracing\Tracer;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\Procedure as ProcedurePlugin;
use Tarantool\Mapper\Procedure;
use Tarantool\Mapper\Repository;

class Application extends Container
{
    use Toolkit;

    private $reflection;

    public function __construct(string $root)
    {
        parent::__construct();

        $fs = new Filesystem($this, $root);

        $this->share(Application::class, $this);
        $this->share(Container::class, $this);
        $this->share(Filesystem::class, $fs);

        $this->addServiceProvider(Provider\ClickhouseProvider::class);
        $this->addServiceProvider(Provider\CoreProvider::class);
        $this->addServiceProvider(Provider\GuzzleProvider::class);
        $this->addServiceProvider(Provider\OpenTelemetryProvider::class);
        $this->addServiceProvider(Provider\PoolProvider::class);
        $this->addServiceProvider(Provider\PredisProvider::class);
        $this->addServiceProvider(Provider\ServiceProvider::class);
        $this->addServiceProvider(Provider\TarantoolProvider::class);

        foreach ($fs->listClasses('Provider') as $provider) {
            $this->addServiceProvider($provider);
        }

        $this->delegate($this->reflection = new ReflectionContainer());

        // toolkit helpers fix
        $this->app = $this;
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service !== null) {
            if ($this->get(Service::class)->getName() == $service) {
                $service = null;
            }
        }

        return $this->get(Cache::class)
            ->wrap([$job, $params, $service], function() use ($job, $params, $service) {
                $span = $this->get(Tracer::class)->createSpan($job);
                foreach ($params as $k => $v) {
                    if (is_numeric($v) || is_string($v)) {
                        $span->setAttribute($k, $v);
                    }
                }

                $serviceName = $this->get(Service::class)->getName();
                $dispatcher = $this->get(Dispatcher::class);
                $runner = $this->get(Runner::class);

                if ($service === null && $runner->hasJob($job)) {
                    $result = $runner->dispatch($job, $params);
                } elseif ($service === null && explode('.', $job)[0] == $serviceName) {
                    $result = $runner->dispatch($job, $params);
                } else {
                    $result = $dispatcher->dispatch($job, $params, $service);
                }
                
                $span->end();
                return $result;
            });
    }

    public function get($alias, bool $new = false) : object
    {
        if (!$this->hasInstance($alias)) {
            $instance = null;
            if (is_subclass_of($alias, Procedure::class)) {
                $instance = $this->get(Mapper::class)
                    ->getPlugin(ProcedurePlugin::class)
                    ->get($alias);
            }
            if (is_subclass_of($alias, Repository::class)) {
                $spaceName = $this->get(Mapper::class)
                    ->getPlugin(Annotation::class)
                    ->getRepositorySpaceName($alias);

                if ($spaceName) {
                    $instance = $this->get(Mapper::class)->getRepository($spaceName);
                }
            }
            if ($instance) {
                $this->share($alias, function () use ($instance) {
                    return $instance;
                });
            }
        }
        return parent::get($alias, $new);
    }

    public function hasInstance($id) : bool
    {
        if ($this->definitions->has($id)) {
            return true;
        }
        if ($this->definitions->hasTag($id)) {
            return true;
        }
        if ($this->providers->provides($id)) {
            return true;
        }
        return false;
    }

    public function has($id) : bool
    {
        if ($this->definitions->has($id)) {
            return true;
        }
        if ($this->definitions->hasTag($id)) {
            return true;
        }
        if ($this->providers->provides($id)) {
            return true;
        }
        if ($this->reflection && $this->reflection->has($id)) {
            return true;
        }
        return false;
    }

    public function call($callback)
    {
        return $this->reflection->call(...func_get_args());
    }
}
