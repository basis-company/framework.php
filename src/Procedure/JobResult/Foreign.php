<?php

namespace Basis\Procedure\JobResult;

use Tarantool\Mapper\Procedure;

class Foreign extends Procedure
{
    public function getParams() : array
    {
        return ['service'];
    }

    public function getBody(): string
    {
        return <<<LUA
        return box.space.job_result:pairs()
            :filter(function(r) return r.service ~= service end)
            :totable()
        LUA;
    }
}
