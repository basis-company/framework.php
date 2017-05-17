<?php

use Tarantool\Mapper\Mapper;

class TarantoolTest extends TestSuite
{
    public function test()
    {
        $this->app->dispatch('tarantool.migrate');

        $mapper = $this->app->get(Mapper::class);
        $note = $mapper->getRepository('note')->create('zzz');
        $this->assertSame($note->message, 'zzz');

        $note->message = 'test';
        $note->save();

        $this->assertNotNull($note->id);
        $this->assertSame($note->message, 'test');
    }
}
