<?php

namespace Basis\Metric;

use Basis\Metric;

class RequestTotalTime extends Metric
{
    public string $type = self::COUNTER;

    public string $help = 'total time taken by all requests';
}
