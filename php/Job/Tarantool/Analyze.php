<?php

namespace Basis\Job\Tarantool;

class Analyze
{
    public function run()
    {
        return [
            'expire' => time() + 364 * 24 * 60 * 60,
            'present' => is_dir('php/Entity'),
        ];
    }
}
