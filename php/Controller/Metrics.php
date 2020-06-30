<?php

namespace Basis\Controller;

use Basis\Metric\Registry;

class Metrics
{
    public function index(Registry $registry)
    {
        return $registry->render();
    }
}
