<?php

namespace Test;

use Basis\Filesystem;
use Basis\Job;
use Basis\Procedure\Select;
use Basis\Test;
use Exception;
use Procedure\Greet;
use ReflectionClass;
use Repository\Note;
use Tarantool\Mapper\Bootstrap;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Pool;

class TarantoolTest extends Test
{
    public $data = [
        'guard.session' => [],
        'web.services' => [
            ['id' => 1, 'name' => 'tester'],
            ['id' => 2, 'name' => 'basis'],
            ['id' => 3, 'name' => 'web'],
        ],
        'tester.data' => [
            ['id' => 3, 'value' => 'test'],
            ['id' => 4, 'value' => 'test'],
        ],
    ];

    public $mocks = [
        ['web.services', [], ['services' => ['web', 'tester', 'basis']]]
    ];

    public function testPool()
    {
        $web = $this->get(Pool::class)->get('web');
        $this->assertCount(3, $web->find('services'));
        $this->assertCount(1, $web->find('services', ['name' => 'web']));
        $this->assertCount(3, $this->find('web.services'));
        $this->assertCount(1, $this->find('web.services', ['name' => 'web']));

        $this->assertSame($this->getRepository('web.services'), $this->get(Pool::class)->get('web')->getRepository('services'));

        $tester = $this->get(Pool::class)->get('tester');
        $this->assertCount(0, $tester->find('data', ['id' => 2]));
        $this->assertCount(1, $tester->find('data', ['id' => 3]));
        $this->assertCount(2, $tester->find('data', ['value' => 'test']));

        $this->data['web.services'][] = ['id' => 4, 'name' => 'guard'];
        $this->assertCount(4, $web->find('services'));

        $this->assertSame($tester->findOne('data', ['value' => 'test'])->id, 3);
        $this->assertSame($tester->findOrFail('data', ['value' => 'test'])->id, 3);
        $this->assertNull($tester->findOne('data', ['id' => 1]));

        $this->expectException(Exception::class);
        $tester->findOrFail('data', ['id' => 1]);
    }

    public function tearDown(): void
    {
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

        parent::tearDown();
    }

    public function testProcedureRegistration()
    {
        $this->assertSame($this->app->get(Greet::class)('Dmitry'), 'Hello, Dmitry!');
    }

    public function testMigrationOrder()
    {
        $migration = $this->app->dispatch('generate.migration', [
            'name' => 'b',
        ]);
        $contents = file_get_contents($migration->filename);
        $contents = str_replace('throw', '//throw', $contents);
        file_put_contents($migration->filename, $contents);

        sleep(1);

        $migration = $this->app->dispatch('generate.migration', [
            'name' => 'a',
        ]);

        $contents = file_get_contents($migration->filename);
        $contents = str_replace('throw', '//throw', $contents);
        file_put_contents($migration->filename, $contents);

        $this->app->dispatch('tarantool.migrate');

        $bootstrap = $this->app->get(Bootstrap::class);

        $reflection = new ReflectionClass(Bootstrap::class);
        $property = $reflection->getProperty('migrations');
        $property->setAccessible(true);

        $migrations = $property->getValue($bootstrap);

        $this->assertCount(3, $migrations);

        $order = [];
        foreach ($migrations as $migration) {
            $order[] = substr($migration, -1);
        }
        $this->assertSame(['e', 'B', 'A'], $order);
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
        $mapper = $this->app->get(Mapper::class);
        $mapper->getRepository('note')->truncate();
        $note = $mapper->getRepository('note')->create('zzz');
        $this->assertSame($note->message, 'zzz');

        $note->message = 'test';
        $note->save();

        $this->assertNotNull($note->id);
        $this->assertSame($note->message, 'test');

        $this->assertSame($note->app, $this->app);

        ob_start();
        var_dump($note);
        $contents = ob_get_clean();

        $this->assertNotContains("app", $contents);
    }

    public function testRepositoryRegistration()
    {
        $repository = $this->app->get(Note::class);
        $this->assertSame($this->app->get(Mapper::class), $repository->getMapper());
    }

    public function testJobShortcuts()
    {
        $job = new class($this->app) extends Job {
            public function run(TarantoolTest $test)
            {
                // dispatch shortcut
                $result = $this->dispatch('test.hello');
                $test->assertSame($result->message, 'hello world!');

                // get instance shortcut
                $mapper = $this->get(Mapper::class);
                $mapper->getRepository('note')->truncate();

                // find shortcut
                $test->assertCount(0, $this->find('note'));
                $note = $this->create('note', ['message' => 'hello world']);
                $test->assertCount(1, $this->find('note'));
                // find one shortcut
                $test->assertNotNull($this->findOne('note', ['id' => $note->id]));
                $test->assertSame([$note], $this->find('note'));

                // find or create shortcut
                $testing = $this->findOrCreate('note', ['id' => $note->id]);
                $test->assertSame($note, $testing);

                $testing = $this->findOrCreate('note', ['id' => $note->id+1]);
                $test->assertCount(2, $this->find('note'));

                // find or fail shortcut
                $this->findOrFail('note', $testing->id);

                // remove shortcut
                $this->remove('note', ['id' => $testing->id]);

                $test->assertNull($this->findOne('note', ['id' => $testing->id]));

                $test->expectException(Exception::class);
                $this->findOrFail('note', $testing->id);
            }
        };

        $job->run($this);

        ob_start();
        var_dump($job);
        $contents = ob_get_clean();

        $this->assertNotContains("app", $contents);
    }

    public function testPoolConfiguration()
    {
        $this->assertSame($this->getMapper()->serviceName, 'test');

        $pool = $this->get(Pool::class);

        $this->mock('web.services')->willReturn(['services' => ['guard']]);
        $this->assertSame('guard', $pool->get('guard')->serviceName);

        $this->expectException(Exception::class);
        $pool->get('gateway');
    }

    public function testSelectRegistration()
    {
        // procedure was registered
        $mapper = $this->getMapper();
        $result = $mapper->getClient()->evaluate("return basis_select(nil, nil, nil)");
        $this->assertNull($result[0]);
    }

    public function testSelectUsage()
    {
        $mapper = $this->getMapper();
        $mapper->getSchema()
            ->createSpace('tester', [
                'id' => 'unsigned',
                'name' => 'string',
            ])
            ->createIndex('id')
            ->createIndex('name');

        $nekufa = $mapper->create('tester', ['id' => 1, 'name' => 'nekufa']);
        $bazyaba = $mapper->create('tester', ['id' => 2, 'name' => 'bazyaba']);

        $result = $this->get(Select::class)
            ('tester', 'id', [$nekufa->id]);

        $this->assertCount(1, $result);

        $result = $this->get(Select::class)
            ('tester', 'id', [$nekufa->id, $nekufa->id]);

        $this->assertCount(1, $result);

        $result = $this->get(Select::class)
            ('tester', 'id', [$bazyaba->id, $nekufa->id, $nekufa->id]);

        $this->assertCount(2, $result);

        $result = $this->get(Select::class)
            ('tester', 'name', ['nekufa']);

        $this->assertCount(1, $result);

        $result = $this->get(Select::class)
            ('tester', 'name', ['nekufa', 'bazyaba']);

        $this->assertCount(2, $result);

        $result = $this->get(Select::class)
            ('tester', 'name', ['nekufa', 'bazyaba', 'nekufa', 'dmitry']);

        $this->assertCount(2, $result);
    }

    public function testSelectUsageWithCompositeKeys()
    {
        $mapper = $this->getMapper();
        $mapper->getSchema()
            ->createSpace('calendar', [
                'year' => 'unsigned',
                'month' => 'unsigned',
                'day' => 'unsigned',
            ])
            ->createIndex(['year', 'month', 'day']);

        $mapper->create('calendar', ['year' => 2018, 'month' => 4, 'day' => 1]);
        $mapper->create('calendar', ['year' => 2018, 'month' => 5, 'day' => 1]);
        $mapper->create('calendar', ['year' => 2018, 'month' => 5, 'day' => 2]);
        $mapper->create('calendar', ['year' => 2018, 'month' => 5, 'day' => 3]);
        $mapper->create('calendar', ['year' => 2018, 'month' => 5, 'day' => 5]);
        $mapper->create('calendar', ['year' => 2018, 'month' => 6, 'day' => 15]);

        $validations = [
            [4, [[2018, 5]]],
            [5, [[2018, 5], [2018, 6]]],
            [2, [[2018, 5, 1], [2018, 5, 2]]],
            [1, [[2018, 5, 3], [2018, 5, 4]]],
            [2, [[2018, 5, 3], [2018, 5, 4], [2018, 4]]],
            [2, [[2018, 5, 3], [2018, 5, 4], [2018, 4], [2018, 4]]],
        ];

        $select = function($key) {
            return $this->get(Select::class)('calendar', 'year_month_day', $key);
        };

        foreach ($validations as [$result, $keys]) {
            $this->assertCount($result, $select($keys));
        }

        $spaceId = $mapper->getClient()->evaluate('return box.space.calendar.id')[0];
        $indexId = $mapper->getClient()->evaluate('return box.space.calendar.index.year_month_day.id')[0];

        $selectUsingSpaceAndIndexId = function($values) use ($spaceId, $indexId) {
            return $this->get(Select::class)($spaceId, $indexId, $values);
        };

        foreach ($validations as [$result, $keys]) {
            $this->assertCount($result, $selectUsingSpaceAndIndexId($keys));
        }
    }

    public function testSelectUsageWithNestedStructures()
    {
        $mapper = $this->getMapper();
        $mapper->getSchema()
            ->createSpace('sector', [
                'id' => 'unsigned',
            ])
            ->addProperty('parent', 'unsigned', [
                'is_nullable' => false,
                'reference' => 'sector'
            ])
            ->createIndex([
                'fields' => ['id'],
            ])
            ->createIndex([
                'fields' => ['parent'],
                'unique' => false,
            ]);

        $root = $this->create('sector', []);
        $moscow = $this->create('sector', ['parent' => $root]);
        $kaluga = $this->create('sector', ['parent' => $root]);
        $obninsk = $this->create('sector', ['parent' => $kaluga]);

        $select = function($values) {
            return $this->get(Select::class)('sector', 'id', $values);
        };

        $validations = [
            [1, [$obninsk->id]],
            [2, [$kaluga->id]],
            [3, [$kaluga->id, $moscow->id]],
            [4, [$obninsk->id, $root->id]],
        ];

        foreach ($validations as [$result, $input]) {
            $this->assertCount($result, $select($input));
        }

        $client = $mapper->getClient();

        $selectUsingNetBox = function($values) use ($client) {
            return $client->evaluate("
                return require('net.box').connect('tcp://localhost:3301')
                    :call('basis_select', {
                        'sector', 'id', ...
                    })
            ", $values)[0];
        };

        foreach ($validations as [$result, $input]) {
            $this->assertCount($result, $selectUsingNetBox($input));
        }

        $spaceId = $client->evaluate("return box.space.sector.id")[0];
        $indexId = $client->evaluate("return box.space.sector.index.id.id")[0];

        $selectUsingNetBoxWithoutNames = function($values) use ($client, $spaceId, $indexId) {
            return $client->evaluate("
                return require('net.box').connect('tcp://localhost:3301')
                    :call('basis_select', {
                        $spaceId, $indexId, ...
                    })
            ", $values)[0];
        };

        foreach ($validations as [$result, $input]) {
            $this->assertCount($result, $selectUsingNetBoxWithoutNames($input));
        }
    }
}

