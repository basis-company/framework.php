<?php

namespace Test;

use Basis\Test;
use Basis\Test\Mapper;
use Basis\Test\Repository;

class MapperTest extends Test
{
    public $data = [
        'flow.tracker' => [
            [], // use id generator
        ],
    ];

    public function testFakeMapper()
    {
        $this->assertCount(1, $this->find('flow.tracker'));
        $this->assertSame(1, $this->findOrFail('flow.tracker')->id);
        $this->assertNotNull($this->findOrFail('flow.tracker', 1));
        $this->assertNull($this->findOne('flow.tracker', 2));

        $tracker = $this->create('flow.tracker', [ 'status' => 'ready', ]);
        $this->assertSame($tracker->status, 'ready');
        $this->assertSame($tracker->id, 2);

        $this->assertCount(2, $this->find('flow.tracker'));
        $this->assertCount(1, $this->find('flow.tracker', [ 'status' => 'ready' ]));

        $this->assertInstanceOf(Repository::class, $this->getRepository('flow.tracker'));

        $mapper = $this->getRepository('flow.tracker')->getMapper();
        $this->assertInstanceOf(Mapper::class, $mapper);
        $this->assertNotNull($mapper);

        $mapper->remove('tracker', [ 'id' => 1 ]);
        $this->assertCount(1, $this->find('flow.tracker'));
        $this->assertSame(2, $this->findOne('flow.tracker')->id);
        $tracker = $this->create('flow.tracker', [ 'status' => 'ready', ]);
        $this->assertSame($tracker->status, 'ready');
        $this->assertSame($tracker->id, 3);
        $this->assertCount(2, $this->find('flow.tracker'));
        $this->assertNotNull($this->findOrFail('flow.tracker', 3));

        $this->create('flow.tracker', ['status' => 'draft']);
        $this->assertCount(3, $this->find('flow.tracker'));

        $this->remove('flow.tracker', ['status' => 'ready']);
        $this->assertCount(1, $this->find('flow.tracker'));
        $this->getRepository('flow.tracker')->remove($this->findOne('flow.tracker'));

        $this->assertCount(0, $this->find('flow.tracker'));
    }
}