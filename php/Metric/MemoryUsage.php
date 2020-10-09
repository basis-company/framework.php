<?php

namespace Basis\Metric;

use Basis\Metric;

class MemoryUsage extends Metric
{
    public string $type = self::GAUGE;
    public string $help = 'memory peak usage';

    public function update()
    {
        return $this->setValue(memory_get_peak_usage(true));
    }
}
