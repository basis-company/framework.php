<?php

namespace Basis\Job\Nats;

use Basis\Application;
use Basis\Attribute\Stream;
use Basis\Dispatcher;
use ReflectionClass;

class Streams
{
    public function run(Application $app, Dispatcher $dispatcher)
    {
        $streams = [
            [
                'name' => $app->getName(),
                'threads' => null,
                'subjects' => [$app->getName()]
            ],
        ];

        return compact('streams');
    }
}
