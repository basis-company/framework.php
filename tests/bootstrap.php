<?php

$loader = require dirname(__DIR__).'/vendor/autoload.php';
$loader->add('', __DIR__);

$loader->setPsr4("Example\\", __DIR__.'/example/src');