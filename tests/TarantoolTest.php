<?php

use Basis\Filesystem;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Mapper;
use Repository\Note;

class TarantoolTest extends TestSuite
{
    public function setup()
    {
        parent::setup();

        $fs = $this->app->get(Filesystem::class);
        $classes = $fs->listClasses('Migration');

        $dirs = [];
        foreach ($classes as $class) {
            $filename = $fs->getPath('php/'.str_replace('\\', '/', $class).'.php');
            unlink($filename);
            $dirs[dirname($filename)] = true;
        }
        foreach ($dirs as $dir => $_) {
            rmdir($dir);
        }
    }

    public function testMigrationOrder()
    {
        $migration = $this->app->dispatch('generate.migration', [
            'name' => 'b',
        ]);
        $contents = file_get_contents($migration['filename']);
        $contents = str_replace('throw', '//throw', $contents);
        file_put_contents($migration['filename'], $contents);

        sleep(1);

        $migration = $this->app->dispatch('generate.migration', [
            'name' => 'a',
        ]);

        $contents = file_get_contents($migration['filename']);
        $contents = str_replace('throw', '//throw', $contents);
        file_put_contents($migration['filename'], $contents);

        $this->app->dispatch('tarantool.migrate');

        $bootstrap = $this->app->get(Bootstrap::class);

        $reflection = new ReflectionClass(Bootstrap::class);
        $property = $reflection->getProperty('migrations');
        $property->setAccessible(true);

        $migrations = $property->getValue($bootstrap);

        $this->assertCount(2, $migrations);

        $order = [];
        foreach ($migrations as $migration) {
            $order[] = substr($migration, -1);
        }
        $this->assertSame(['B', 'A'], $order);
    }

    public function testMigrationGenerator()
    {
        $fs = $this->app->get(Filesystem::class);

        $classes = $fs->listClasses('Migration');
        $this->assertCount(0, $classes);

        $this->app->dispatch('generate.migration', [
            'name' => 'my migration created at ' . time(),
        ]);

        $classes = $fs->listClasses('Migration');
        $this->assertCount(1, $classes);
    }

    public function testEntity()
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

        $this->assertSame($note->app, $this->app);
    }

    public function testRepositoryRegistration()
    {
        $repository = $this->app->get(Note::class);
        $this->assertSame($this->app->get(Mapper::class), $repository->getMapper());
    }
}
