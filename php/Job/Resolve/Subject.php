<?php

namespace Basis\Job\Resolve;

use Basis\Container;
use Basis\Nats\Client;

class Subject
{
    public string $job;
    public string $service;

    public function __construct(
        public readonly Container $container,
    ) {
    }

    public function run()
    {
        return [
            'subject' => $this->getSubject(),
            'expire' => PHP_INT_MAX
        ];
    }

    private function getSubject(): ?string
    {
        if (getenv('BASIS_ENVIRONMENT') !== 'testing') {
            $api = $this->container->get(Client::class)->getApi();
            $subject = str_replace('.', '_', $this->job);

            foreach ([$subject, $this->service] as $name) {
                if ($api->getStream($name)->exists()) {
                    return $name;
                }
            }
        }

        return null;
    }
}
