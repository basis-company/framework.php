<?php

namespace Basis\Metric;

use Basis\Metric;
use Tarantool\Client\Client;

class JobQueueLength extends Metric
{
    public string $help = 'job queue length';
    public string $type = self::GAUGE;

    public function update(Client $client)
    {
        [$value] = $client->call('box.space.job_queue:count');
        $this->setValue($value);
    }
}
