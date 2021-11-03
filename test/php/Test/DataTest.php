<?php

namespace Test;

use Basis\Data;
use Basis\Test;
use Tarantool\Mapper\Mapper;

class DataTest extends Test
{
    public function testCrud()
    {
        $crud = $this->get(Data::class)->getCrud('my_sharded_space');

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

        // delete row
        $instance = $crud->delete('username');
        $this->assertSame($instance['key'], 'username');
    }

    public function testMigrationsAreApplied()
    {
        $mapper = new Mapper($this->get(Data::class)->getClient());
        $this->assertTrue($mapper->getSchema()->hasSpace('my_sharded_space'));
    }
}
