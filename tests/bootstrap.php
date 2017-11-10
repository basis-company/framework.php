<?php

$loader = require dirname(__DIR__).'/vendor/autoload.php';

$loader->setPsr4("", __DIR__.'/example/php');
$loader->setPsr4("Test\\", __DIR__);
