<?php

namespace Basis\Metric;

use Basis\Metric;

class Uptime extends Metric
{
    public string $type = self::COUNTER;

    public string $help = 'uptime in seconds';

    public function update($startTime)
    {
        $this->setValue(round(time() - $startTime));
    }
}
