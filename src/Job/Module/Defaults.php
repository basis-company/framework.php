<?php

namespace Basis\Job\Module;

use Basis\Filesystem;
use Basis\Framework;
use Basis\Job;

class Defaults extends Job
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
