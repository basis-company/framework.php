<?php

namespace Basis\Job\Tarantool;

use Basis\Cache as CacheComponent;
use Basis\Filesystem;
use Basis\Job;
use Tarantool\Mapper\Mapper;

class Cache extends Job
{
    public function run(Mapper $mapper, Filesystem $fs, CacheComponent $cache)
    {
        $cache->set('mapper-meta', $mapper->getMeta());
    }
}
