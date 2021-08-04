<?php

namespace Basis\Test;

use ArrayObject;
use Basis\Metric;
use Basis\Metric\Registry;

class MetricRegistry extends Registry
{
    protected $data = [];

    public function getValue(Metric $metric, array $labels = [])
    {
        $key = $this->getKey($metric, $labels);
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    public function setValue(Metric $metric, array $labels, $value)
    {
        $key = $this->getKey($metric, $labels);

        return $this->data[$key] = $value;
    }

    public function increment(Metric $metric, array $labels, $amount)
    {
        return $this->data[$this->getKey($metric, $labels)] += $amount;
    }

    protected function getMetrics()
    {
        return $this->data;
    }
}
