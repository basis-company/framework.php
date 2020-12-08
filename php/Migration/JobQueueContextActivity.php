<?php

namespace Basis\Migration;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;

class JobQueueContextActivity implements Migration
{
    public $created_at = '2020-12-06 00:26:34';

    public function migrate(Mapper $mapper)
    {
        // process data
        $mapper->getClient()->evaluate(<<<LUA
            box.space.job_context:pairs()
                :each(function(t) 
                    box.space.job_context:update(t.id, {{'=', 4, 0}})
                end)
        LUA);

        // update context
        $mapper->getSchema()
            ->getSpace('job_context')
            ->addProperty('activity', 'number', [
                'is_nullable' => true,
            ])
            ->addIndex([
                'fields' => ['activity'],
                'name' => 'activity',
                'type' => 'tree',
                'unique' => false,
            ]);
    }
}
