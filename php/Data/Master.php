<?php

namespace Basis\Data;

use Basis\Dispatcher;
use Tarantool\Client\Client;

class Master
{
    private array $context = [];
    private array $wrappers = [];
    private array $serviceWrappers = [];
    private string $service;

    public function __construct(private Dispatcher $dispatcher)
    {
        $this->service = $dispatcher->getServiceName();
    }

    public function getContext(string $space): Context
    {
        if (!array_key_exists($space, $this->context)) {
            if (strpos($space, '.') !== false) {
                if (!in_array(explode('.', $space)[0], ['vshard', 'crud'])) {
                    return $this->context[$space] = new Context(...explode('.', $space, 2));
                }
            }
            return $this->context[$space] = new Context($this->service, $space);
        }

        return $this->context[$space];
    }

    public function get(string $class, string $space)
    {
        $context = $this->getContext($space);

        return $this->getWrapper($context->getService())
                    ->get($class, $context->getName());
    }

    public function getCrud(string $space): Crud
    {
        return $this->get(Crud::class, $space);
    }

    public function getProcedure(string $name): Procedure
    {
        return $this->get(Procedure::class, $name);
    }

    public function getWrapper(string $service = ''): Wrapper
    {
        if (!$service) {
            $service = $this->service;
        }

        if (!array_key_exists($service, $this->serviceWrappers)) {
            $dsn = false;

            if ($service == $this->service) {
                $dsn = getenv('DATA_CONNECTION');
            }

            if (!$dsn) {
                $hostname = $service . '-data';
                $password = getenv('DATA_PASSWORD') ?: 'password';
                $port = getenv('DATA_PORT') ?: 3301;
                $resolve = $this->dispatcher->dispatch('resolve.address', [ 'name' => $hostname ]);
                $username = getenv('DATA_USERNAME') ?: 'admin';
                $dsn = 'tcp://' . $username . ':' . $password . '@' . $resolve->host . ':' . $port;
            }

            $this->serviceWrappers[$service] = $this->getWrapperByDsn($dsn, $service);
        }

        return $this->serviceWrappers[$service];
    }

    public function getWrapperByDsn(string $dsn, string $service = ''): Wrapper
    {
        if (!array_key_exists($dsn, $this->wrappers)) {
            $this->wrappers[$dsn] = new Wrapper($service ?: $this->service, Client::fromDsn($dsn));
        }

        return $this->wrappers[$dsn];
    }
}
