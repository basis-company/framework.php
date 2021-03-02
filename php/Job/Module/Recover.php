<?php

namespace Basis\Job\Module;

use Basis\Toolkit;
use Throwable;

class Recover
{
    use Toolkit;

    public int $timeout = 5 * 60;

    public function run()
    {
        $script = <<<LUA
            box.begin()
            local new = box.space.job_queue.index.status_id:pairs('processing')
                :filter(function(t) return t[9] == nil end)
                :map(function(t)
                    return box.space.job_queue:update(t.id, {{'=', 9, { since = TIMESTAMP }}})
                end)
                :totable()
            local recovered = box.space.job_queue.index.status_id:pairs('processing')
                :filter(function(t) return t[9] ~= nil and t[9]['since'] <= TIMEOUT end)
                :map(function(t)
                    return box.space.job_queue:update(t.id, {{'=', 2, 'new'}, { '=', 9, box.NULL}})
                end)
                :totable()
            box.commit()
            return { new = #new, recovered = #recovered }
        LUA;

        $script = str_replace('TIMESTAMP', time(), $script);
        $script = str_replace('TIMEOUT', time() - $this->timeout, $script);

        return $this->getMapper()->getClient()->evaluate($script)[0];
    }
}
