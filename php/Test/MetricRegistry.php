<?php

namespace Basis\Test;

use ArrayObject;
use Basis\Metric;
use Basis\Metric\Registry;

class MetricRegistry extends Registry
{
    public function __construct()
    {
        $this->table = new ArrayObject();
    }

    public function getRow(Metric $metric, $labels = [])
    {
        $labels = count($labels) ? json_encode($labels) : '';
        if (!$this->table->offsetExists($metric->getNick() . $labels)) {
            $this->table[$metric->getNick() . $labels] = new ArrayObject([
                'help' => $metric->getHelp(),
                'nick' => $metric->getNick(),
                'type' => $metric->getType(),
                'labels' => $labels,
            ]);
        }

        return $this->table[$metric->getNick() . $labels];
    }
}
