<?php

namespace Basis\Test;

use Basis\Application as Basis;
use Basis\Registry;
use Basis\Metric\Registry as Metrics;
use Basis\Test;

class Application extends Basis
{
    public function __construct(Test $test)
    {
        parent::__construct();

        $container = $this->getContainer();
        $container->share(Test::class, $test);
        $container->share(Metrics::class, new MetricRegistry($this->app));

        $classes = $this->get(Registry::class)->listClasses('Test');
        foreach ($classes as $class) {
            if (strpos($class, 'Basis\\Test') === false) {
                continue;
            }
            $origin = str_replace('Test\\', '', $class);
            if (!class_exists($origin)) {
                continue;
            }
            $container->share($origin, $class);
        }
    }
}
