<?php

namespace Basis\Migration;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;

class JobQueue implements Migration
{
    public $created_at = '2019-12-13 19:26:34';

    public function migrate(Mapper $mapper)
    {
        $mapper->getSchema()
            ->createSpace('job_context')
            ->addProperty('id', 'integer')
            ->addProperty('hash', 'string')
            ->addProperty('context', '*')
            ->addIndex(['id'])
            ->addIndex(['hash']);

        $mapper->getSchema()
            ->createSpace('job_queue')
            ->addProperty('id', 'integer')
            ->addProperty('status', 'string')
            ->addProperty('hash', 'string')
            ->addProperty('context', 'integer', [ 'is_nullable' => false, 'reference' => 'job_context' ])
            ->addProperty('service', 'string', [ 'is_nullable' => false ])
            ->addProperty('job', 'string', [ 'is_nullable' => false ])
            ->addProperty('params', '*', [ 'is_nullable' => false ])
            ->addProperty('recipient', 'string', [ 'is_nullable' => true ])
            ->addIndex(['id'])
            ->addIndex(['hash', 'status', 'id'])
            ->addIndex(['status', 'id']);

        $mapper->getSchema()
            ->createSpace('job_result')
            ->addProperty('id', 'integer')
            ->addProperty('service', 'string')
            ->addProperty('hash', 'string')
            ->addProperty('data', 'map', [ 'is_nullable' => false ])
            ->addProperty('expire', 'integer')
            ->addIndex(['id'])
            ->addIndex(['service', 'hash'])
            ->addIndex(['expire', 'id']);

        $sequence = $mapper->getPlugin(Sequence::class);
        $schema = $mapper->getSchema();
        $sequence->initializeSequence($schema->getSpace('job_queue'));
        $sequence->initializeSequence($schema->getSpace('job_result'));
    }
}
