<?php

namespace Basis\Metric;

use Basis\Metric;

class Uptime extends Metric
{
    public string $help = 'uptime in seconds';

    public function update()
    {
        $this->setValue(round(time() - $this->get(StartTime::class)->getValue()));
    }
}
