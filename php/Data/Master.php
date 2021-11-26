<?php

namespace Basis\Data;

use Basis\Dispatcher;
use Tarantool\Client\Client;

class Master
{
    private array $context = [];
    private array $wrappers = [];
    private string $service;

    public function __construct(private Dispatcher $dispatcher)
    {
        $this->service = $dispatcher->getServiceName();
    }

    public function getContext(string $space): Context
    {
        if (!array_key_exists($space, $this->context)) {
            if (strpos($space, '.') === false) {
                $this->context[$space] = new Context($this->service, $space);
            } else {
                $this->context[$space] = new Context(...explode('.', $space, 2));
            }
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

        if (!array_key_exists($service, $this->wrappers)) {
            $uri = false;

            if ($service == $this->service) {
                $uri = getenv('DATA_CONNECTION');
            }

            if (!$uri) {
                $hostname = $this->dispatcher->getServiceName() . '-data';
                $resolve = $this->dispatcher->dispatch('resolve.address', [ 'name' => $hostname ]);
                $port = getenv('DATA_PORT') ?: 3301;
                $uri = 'tcp://' . $resolve->host . ':' . $port;
            }

            $client = Client::fromOptions([
                'uri' => $uri,
                'username' => getenv('DATA_USERNAME') ?: 'admin',
                'password' => getenv('DATA_PASSWORD') ?: 'password',
            ]);

            $this->wrappers[$service] = new Wrapper($service, $client);
        }

        return $this->wrappers[$service];
    }
}
