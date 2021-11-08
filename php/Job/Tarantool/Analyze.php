<?php

namespace Basis\Job\Tarantool;

class Analyze
{
    public function run()
    {
        return [
            'expire' => PHP_INT_MAX,
            'present' => is_dir('php/Entity'),
        ];
    }
}
