<?php

namespace Basis\Metric;

use Basis\Metric;

class BackgroundStart extends Metric
{
    public string $help = '';

    public function update()
    {
        return $this->setValue(time());
    }
}
