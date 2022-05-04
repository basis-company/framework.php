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
        'flow.status' => [],
    ];

    public function testVirtialEntiyRepositoryGetter()
    {
        $tracker = $this->create('flow.tracker', [
            'status' => $this->findOrCreate('flow.status'),
        ]);

        $this->assertSame($tracker->status, $this->findOne('flow.status')->id);
    }

    public function testApplicationInstance()
    {
        $vspace = $this->findOrFail('_vspace');
        $this->assertSame($vspace->app, $this->app);
    }

    public function testEntityInheritance()
    {
        $status = $this->create('flow.status', []);
        $this->create('flow.tracker', [
            'status' => $status // create reference for id
        ]);
        $data = $this->find('flow.tracker', [
            'status' => $status->id
        ]);
        $this->assertCount(1, $data);
    }

    public function testFakeMapper()
    {
        $this->assertCount(1, $this->find('flow.tracker'));
        [$first] = $this->find('flow.tracker');
        $this->assertNotNull($this->findOrFail('flow.tracker', $first->id));
        $this->assertNotNull($this->findOrFail('flow.tracker')->id);
        $this->assertNull($this->findOne('flow.tracker', 2));

        $second = $this->create('flow.tracker', [ 'status' => 'ready', ]);
        $this->assertSame($second->status, 'ready');
        $this->assertSame($second->id, $first->id + 1);

        $this->assertCount(2, $this->find('flow.tracker'));
        $this->assertCount(1, $this->find('flow.tracker', [ 'status' => 'ready' ]));

        $this->assertInstanceOf(Repository::class, $this->getRepository('flow.tracker'));

        $mapper = $this->getRepository('flow.tracker')->getMapper();
        $this->assertInstanceOf(Mapper::class, $mapper);
        $this->assertNotNull($mapper);

        $mapper->remove('tracker', [ 'id' => $first->id ]);
        $this->assertCount(1, $this->find('flow.tracker'));
        $this->assertSame($second->id, $this->findOne('flow.tracker')->id);
        $third = $this->create('flow.tracker', [ 'status' => 'ready', ]);
        $this->assertSame($third->status, 'ready');
        $this->assertSame($third->id, $first->id + 2);
        $this->assertCount(2, $this->find('flow.tracker'));
        $this->assertNotNull($this->findOrFail('flow.tracker', $third->id));

        $this->create('flow.tracker', ['status' => 'draft']);
        $this->assertCount(3, $this->find('flow.tracker'));

        $this->remove('flow.tracker', ['status' => 'ready']);
        $this->assertCount(1, $this->find('flow.tracker'));
        $this->getRepository('flow.tracker')->remove($this->findOne('flow.tracker'));

        $this->assertCount(0, $this->find('flow.tracker'));

        $fourth = $this->create('flow.tracker', []);
        $fourth->status = 'ready';
        $fourth->save();
        // id should be started randomized
        $this->assertNotSame($fourth->id, $third->id + 1);

        $tracker = $this->findOne('flow.tracker', [ 'status' => 'ready']);
        $this->assertSame($tracker->status, 'ready');

        $tracker = $this->findOrCreate('flow.tracker', [ 'id' => 27, 'author' => 'nekufa' ]);
        $this->assertNotNull($tracker);
        $this->assertSame($tracker->id, 27);

        $trackerByAuthor = $this->findOrCreate('flow.tracker', [ 'author' => 'nekufa' ]);
        $this->assertNotNull($trackerByAuthor);
        $this->assertSame($tracker, $trackerByAuthor);
    }
}
