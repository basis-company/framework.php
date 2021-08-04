<?php

namespace Basis\Metric;

use Basis\Metric;

class StartTime extends Metric
{
    public string $help = 'startup timestamp';

    public function update()
    {
        $this->setValue(time());
    }
}
