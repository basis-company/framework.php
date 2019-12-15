<?php

namespace Basis\Procedure\JobQueue;

use Tarantool\Mapper\Procedure;

class Take extends Procedure
{
    public function getParams() : array
    {
        return [];
    }

    public function getBody(): string
    {
        return <<<LUA
        box.begin()
        local tuples = box.space.job_queue.index.status_id:select({'new'}, { limit = 1})
        if #tuples > 0 then
            local request = box.space.job_queue:update(tuples[1].id, {{'=', 2, 'processing'}})
            box.commit()
            return request
        end
        box.rollback()
        LUA;
    }
}
