<?php

namespace Basis\Job\Module;

use Basis\Job;
use Exception;

class Contents extends Job
{
    public $path;

    public function run()
    {
        if (!$this->path) {
            throw new Exception("Invalid path {$this->path}", 1);
        }

        $contents = file_get_contents($this->path);

        return compact('contents');
    }
}
