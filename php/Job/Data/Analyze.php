<?php

namespace Basis\Job\Data;

class Analyze
{
    public function run()
    {
        return [
            'expire' => time() + 364 * 24 * 60 * 60,
            'present' => is_dir('lua/migrations'),
        ];
    }
}
