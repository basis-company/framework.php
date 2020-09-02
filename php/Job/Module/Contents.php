<?php

namespace Basis\Job\Module;

use Basis\Job;
use Exception;

class Contents extends Job
{
    public string $path;

    public function run()
    {
        if (!$this->path) {
            throw new Exception("Invalid path {$this->path}");
        }

        $contents = file_get_contents($this->path);

        return compact('contents');
    }
}
