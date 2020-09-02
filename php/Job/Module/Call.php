<?php

namespace Basis\Job\Module;

use Basis\Job;
use Exception;

class Call extends Job
{
    public int $id;
    public string $method;
    public string $space;

    public function run()
    {
        $instance = $this->findOrFail($this->space, $this->id);

        if (!method_exists($instance, $this->method)) {
            throw new Exception("Method $this->method not defined");
        }

        $method = $this->method;

        return [
            'result' => $instance->$method(),
        ];
    }
}
