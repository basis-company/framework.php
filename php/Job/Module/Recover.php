<?php

namespace Basis\Job\Module;

use Basis\Toolkit;
use Throwable;

class Recover
{
    use Toolkit;

    public function run()
    {
        $this->getMapper()
            ->getClient()
            ->evaluate(<<<LUA
                box.begin()
                box.space.job_queue.index.status_id:pairs('processing')
                    :each(function(t)
                      box.space.job_queue:update(t.id, {{'=', 2, 'new'}})
                    end)
                box.commit()
                LUA);
    }
}
