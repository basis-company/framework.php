<?php

namespace Basis\Metric;

use Basis\Metric;

class MemoryUsage extends Metric
{
    public string $help = 'memory peak usage';

    public function update()
    {
        return $this->setValue(memory_get_peak_usage(true));
    }
}
