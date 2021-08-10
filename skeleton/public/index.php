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
    echo $app->get(Http::class)->process($_SERVER['REQUEST_URI']);

    if ($app->get(Http::class)->getLogging()) {
        $app->get(RequestCounter::class)->increment();
        $app->get(RequestTotalTime::class)->increment((microtime(true) - $start) * 1000);
    }
} catch (Exception $e) {
    echo $e->getMessage();

    $app->get(RequestTotalTime::class)
        ->increment((microtime(true) - $start) * 1000);
}
