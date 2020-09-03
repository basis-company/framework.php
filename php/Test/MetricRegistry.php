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

    public function getRow(Metric $metric)
    {
        if (!$this->table->offsetExists($metric->getNick())) {
            $this->table[$metric->getNick()] = new ArrayObject([
                'help' => $metric->getHelp(),
                'nick' => $metric->getNick(),
                'type' => $metric->getType(),
            ]);
        }

        return $this->table[$metric->getNick()];
    }
}
