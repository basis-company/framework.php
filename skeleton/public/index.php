<?php

use Basis\Application;
use Basis\Configuration\Monolog;
use Basis\Configuration\Telemetry;
use Basis\Http;
use Basis\Metric\RequestCounter;
use Basis\Metric\RequestTotalTime;

chdir(dirname(__DIR__));

$start = microtime(true);

include 'vendor/autoload.php';

try {
    $application = new Application(dirname(__DIR__));
    $application->get(Monolog::class)->setName('http');
    $application->get(Telemetry::class)->setName('http');
    echo $application->get(Http::class)->process($_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    echo $e->getMessage();
}

if ($application !== null) {
    $application->get(RequestCounter::class)->increment();
    $application->get(RequestTotalTime::class)->increment((microtime(true) - $start) * 1000);
    $application->dispatch('module.flush');
}
