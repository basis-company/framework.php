<?php

$loader = require dirname(__DIR__) . '/vendor/autoload.php';

chdir(__DIR__);
$loader->setPsr4('', __DIR__ . '/php');
