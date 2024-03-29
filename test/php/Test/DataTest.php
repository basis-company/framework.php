<?php

namespace Test;

use Basis\Data\Master;
use Basis\Data\Wrapper;
use Basis\Test;
use Tarantool\Mapper\Mapper;

class DataTest extends Test
{
    public function testMultipleCrud()
    {
        $crud1 = $this->getCrud('my_sharded_space');
        $crud2 = $this->getCrud('another_one');
        $this->assertNotSame($crud1, $crud2);
    }

    public function testProcedures()
    {
        $greet = $this->getProcedure('greet');
        $this->assertSame($greet(), 'hello, world!');
        $this->assertSame($greet('nekufa'), 'hello, nekufa!');

        // service prefixed alias
        $greet = $this->getProcedure('test.greet');
        $this->assertSame($greet(), 'hello, world!');
        $this->assertSame($greet('nekufa'), 'hello, nekufa!');

        // function should be registered at runtime
        [$result] = $this->get(Master::class)
            ->getWrapper()
            ->getClient()
            ->evaluate('return greet()');

        $this->assertSame($result, 'hello, world!');

        // so vshard router can call it
        $result = $this->getProcedure('vshard.router.callrw')(1, 'greet');
        $this->assertSame($result, 'hello, world!');
    }

    public function testCrud()
    {
        // get local crud instance
        $crud = $this->getCrud('my_sharded_space');

        // bucket_id was set using ddl_sharding_key
        $instance = $crud->insert(['key' => 'username', 'value' => 'nekufa']);
        $this->assertNotNull($instance['bucket_id']);

        // get row by primary key
        $instance = $crud->get('username');
        $this->assertSame($instance['key'], 'username');
        $this->assertSame($instance['value'], 'nekufa');
        $this->assertNotNull($instance['bucket_id']);

        // get single field instance
        $instance = $crud->get('username', ['fields' => ['value']]);
        $this->assertSame($instance, ['value' => 'nekufa']);

        // update
        $instance = $crud->update('username', [['=', 'value', 'Dmitry Krokhin']]);
        $this->assertNotNull($instance);
        $this->assertSame($instance['value'], 'Dmitry Krokhin');

        // replace (update)
        $instance = $crud->replace(['key' => 'username', 'value' => 'Dmitry']);
        $this->assertNotNull($instance);
        $this->assertSame($instance['value'], 'Dmitry');

        // replace (insert)
        $instance = $crud->replace(['key' => 'company', 'value' => 'Basis Company']);
        $this->assertNotNull($instance);
        $this->assertSame($instance['value'], 'Basis Company');

        // update non-existing row
        $instance = $crud->update('domain', [['=', 'value', 'nekufa.ru']]);
        $this->assertNull($instance);

        // upsert (insert, no operations are applied)
        $crud->upsert(['key' => 'dns', 'value' => '1.1.1.1'], [['=', 'value', '1.0.0.1']]);
        $this->assertSame($crud->get('dns')['value'], '1.1.1.1');

        // upsert (duplicate by key, operations are applied)
        $crud->upsert(['key' => 'dns', 'value' => '8.8.8.8'], [['=', 'value', '8.8.4.4']]);
        $this->assertSame($crud->get('dns')['value'], '8.8.4.4');

        // get invalid row
        $instance = $crud->get('domain');
        $this->assertNull($instance);

        $instances = $crud->select([['==', 'key', 'username']]);
        $this->assertCount(1, $instances);
        $this->assertSame($instances[0]['value'], 'Dmitry');

        // delete row
        $instance = $crud->delete('username');
        $this->assertSame($instance['key'], 'username');

        // valid expection should be thrown
        $this->expectExceptionMessage("Unknown field");
        $crud->insert(['mail' => 'nekufa@gmail.com']);
    }

    public function testDefaultWrapper()
    {
        // default wrapper for current service
        $this->assertSame($this->get(Master::class)->getWrapper()->getService(), 'test');

        // default wrapper container registration
        $this->assertSame($this->get(Master::class)->getWrapper(), $this->get(Wrapper::class));
    }

    public function testIdentityMap()
    {
        // same instances with prefix
        $this->assertSame($this->getCrud('my_sharded_space'), $this->getCrud('test.my_sharded_space'));
    }

    public function testMigrationsAreApplied()
    {
        $mapper = new Mapper($this->get(Master::class)->getWrapper()->getClient());
        $this->assertTrue($mapper->getSchema()->hasSpace('my_sharded_space'));
    }
}
