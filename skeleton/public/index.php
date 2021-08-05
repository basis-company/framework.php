<?php

use Basis\Application;
use Basis\Http;
use Basis\Metric\RequestCounter;
use Basis\Metric\RequestTotalTime;

chdir(dirname(__DIR__));

$start = microtime(true);
include 'vendor/autoload.php';

try {
    $app = new Application(dirname(__DIR__));
    $app->get(RequestCounter::class)
        ->increment();

    echo $app->get(Http::class)->process($_SERVER['REQUEST_URI']);

    $app->get(RequestTotalTime::class)
        ->increment((microtime(true) - $start) * 1000);
} catch (Exception $e) {
    echo $e->getMessage();

    $app->get(RequestTotalTime::class)
        ->increment((microtime(true) - $start) * 1000);
}
