<?php

namespace Job;

use Basis\Job;

class Random extends Job
{
    public function run()
    {
        return [
            'expire' => PHP_INT_MAX,
            'value' => bin2hex(random_bytes(8)),
            'actor' => $this->findOrFail('_space'),
        ];
    }
}
