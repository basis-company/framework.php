<?php

use Tarantool\Mapper\Mapper;

use Repository\Notes;

class TarantoolTest extends TestSuite
{
    public function test()
    {
        $this->app->dispatch('tarantool.migrate');

        $mapper = $this->app->get(Mapper::class);
        $mapper->getRepository('note')->truncate();
        $note = $mapper->getRepository('note')->create('zzz');
        $this->assertSame($note->message, 'zzz');

        $note->message = 'test';
        $note->save();

        $this->assertNotNull($note->id);
        $this->assertSame($note->message, 'test');
    }

    public function testRepositoryRegistration()
    {
        $this->app->dispatch('tarantool.migrate');

        $repository = $this->app->get(Notes::class);
        $this->assertSame($this->app->get(Mapper::class), $repository->getMapper());
    }
}
