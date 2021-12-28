<?php

namespace Basis\Job\Resolve;

use Basis\Nats\Client;

class Subject
{
    public string $job;
    public string $service;

    public function __construct(
        public readonly Client $client,
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
            $api = $this->client->getApi();
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
