<?php

namespace Basis\Procedure\JobQueue;

use Tarantool\Mapper\Procedure;

class Cleanup extends Procedure
{
    public function getParams(): array
    {
        return [];
    }

    public function getBody(): string
    {
        return <<<LUA
        box.begin()
        for i, t in box.space.job_queue.index.status_id:pairs('transfered') do
            local result = box.space.job_result.index.service_hash:count({t.recipient, t.hash})
            if result > 0 then
                box.space.job_queue:delete(t.id)
            end
        end
        box.commit()
        LUA;
    }
}
