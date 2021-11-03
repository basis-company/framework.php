<?php

namespace Basis\Job\Data;

class Analyze
{
    public function run()
    {
        return [
            'expire' => PHP_INT_MAX,
            'present' => is_dir('lua/migrations'),
        ];
    }
}
