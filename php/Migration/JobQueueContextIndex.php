<?php

namespace Basis\Migration;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;

class JobQueueContextIndex implements Migration
{
    public $created_at = '2020-12-29 09:26:34';

    public function migrate(Mapper $mapper)
    {
        $mapper->getSchema()
            ->getSpace('job_queue')
            ->addIndex([
                'fields' => ['context'],
                'name' => 'context',
                'type' => 'tree',
                'unique' => false,
            ]);
    }
}
