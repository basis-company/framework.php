<?php

namespace Basis\Migration;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;

class JobQueueSequence implements Migration
{
    public $created_at = '2020-12-11 19:26:34';

    public function migrate(Mapper $mapper)
    {
        $sequence = $mapper->getPlugin(Sequence::class);
        $schema = $mapper->getSchema();
        $sequence->initializeSequence($schema->getSpace('job_context'));
        $sequence->initializeSequence($schema->getSpace('job_queue'));
        $sequence->initializeSequence($schema->getSpace('job_result'));
    }
}
