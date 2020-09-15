<?php

namespace Basis\Metric;

use Basis\Metric;

class RequestCounter extends Metric
{
    public string $type = self::COUNTER;

    public string $help = 'processed request counter';
}
