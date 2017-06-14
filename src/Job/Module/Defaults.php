<?php

namespace Basis\Job\Module;

use Basis\Dispatcher;
use Basis\Event;
use Basis\Filesystem;
use Basis\Framework;
use Basis\Runner;
use Basis\Service;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class Defaults
{
    public function run(Framework $framework, Filesystem $fs)
    {
        foreach ($framework->listFiles('resources/default') as $file) {
            if (!$fs->exists($file)) {
                $source = $framework->getPath("resources/default/$file");
                $destination = $fs->getPath($file);
                file_put_contents($destination, file_get_contents($source));
            }
        }
    }
}
