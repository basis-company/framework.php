<?php

namespace Basis\Procedure\JobQueue;

use Tarantool\Mapper\Procedure;

class Cast extends Procedure
{
    public function getParams() : array
    {
        return ['hash', 'tuple'];
    }

    public function getBody(): string
    {
        return <<<LUA
            box.begin()
            local creator = mapper_find_or_create('job_queue', 'hash_status', { hash, 'r' }, tuple, 1, 1)
            box.commit()
            return creator
        LUA;
    }
}
