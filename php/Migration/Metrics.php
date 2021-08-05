<?php

namespace Basis\Migration;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Sequence;

class Metrics implements Migration
{
    public $created_at = '2021-08-04 13:16:34';

    public function migrate(Mapper $mapper)
    {
        $mapper->getSchema()
            ->createSpace('metric')
            ->addProperty('key', 'string')
            ->addProperty('class', 'string')
            ->addProperty('labels', '*')
            ->addProperty('value', 'number')
            ->addIndex(['key']);
    }
}
