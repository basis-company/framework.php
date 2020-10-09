<?php

namespace Basis\Metric;

use Basis\Metric;

class BackgroundHold extends Metric
{
    public string $type = self::GAUGE;
    public string $help = '';

    public function update()
    {
        $value = $this->get(BackgroundStart::class)->getValue();
        if ($value) {
            $this->setValue(time() - $value);
        }
    }
}
