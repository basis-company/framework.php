<?php

use Basis\Filesystem;
use League\Container\Container;
use Tarantool\Mapper\Migrations\Migrator;

class MapperTest extends TestSuite
{
    function tearDown()
    {
        $migrations = $this->app->get(Filesystem::class)->getPath('resources/migrations');
        if (PHP_OS === 'Windows') {
            exec("rd /s /q $migrations");
        } else {
            exec("rm -rf $migrations");
        }
    }

    function testCreateMigration()
    {
        $migration = $this->app->dispatch('generate.migration', [
            'test'
        ]);

        $this->assertSame($migration['class'], 'Test');
        $this->assertSame($migration['namespace'], date('FY'));

        $migrator = $this->app->get(Migrator::class);
        $migrations = $migrator->getMigrations();

        $this->assertCount(1, $migrations);
        $this->assertSame($migrations[0], date('FY').'\\Test');
    }

    function testMigration()
    {
        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $migrator = $this->getMockBuilder(Migrator::class)
            ->setMethods(['migrate'])
            ->getMock();

        $container = $this->app->get(Container::class);

        $migrator->expects($this->once())
            ->method('migrate')
            ->will($this->returnValue('OK'));

        $container->share(Migrator::class, $migrator);
        $container->share(Manager::class, $manager);

        $this->app->dispatch('tarantool.migrate');
    }
}