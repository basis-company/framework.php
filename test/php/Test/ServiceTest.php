<?php

namespace Test;

use Basis\Event;
use Basis\Http;
use Basis\Test;
use Tarantool\Mapper\Pool;

class ServiceTest extends Test
{
    public function testHostResolver()
    {
        $event = $this->get(Event::class);
        $result = [];
        foreach ([1, 2] as $attempt) {
            $start = microtime(1);
            $host = $this->dispatch('resolve.address', ['name' => 'ya.ru']);
            $timer = microtime(1) - $start;
            $result[] = [
                'host' => $host,
                'timer' => $timer,
            ];
        }

        $this->assertGreaterThan($result[1]['timer'], $result[0]['timer']);
        $this->assertEquals($result[0]['host'], $result[1]['host']);
    }

    public function testBootstrap()
    {
        $context = (object) [
            'routes' => [],
        ];

        $this->mock('web.services')->willReturn([
            'services' => []
        ]);

        $this->mock('event.subscribe')->willReturn([
            'success' => true
        ]);

        $this->mock('web.register')->willDo(function ($params) use ($context) {
            foreach ($params['routes'] as $route) {
                $context->routes[] = (object) ['route' => $route, 'service' => $params['service']];
            }
        });

        // internaly it should analyze routes and call web.register
        // then context will be updated and we should validate it
        $this->dispatch('module.register');

        $http = $this->get(Http::class);
        $routes = $http->getRoutes();

        $this->assertContains('dynamic/*', $routes);
        $this->assertContains('index/index', $routes);
        $this->assertContains('index/hello', $routes);
    }

    public function testName()
    {
        $this->assertSame('test', $this->app->getName());
    }

    public function testEvents()
    {
        $event = $this->get(Event::class);

        $this->mock('web.services')->willReturn(['services' => ['web']]);

        $this->mock('event.subscribe')->willReturn(['success' => true]);

        $this->get(Pool::class)->registerResolver(function ($name) {
            if ($name == 'event') {
                return new class {
                    public function find()
                    {
                        return [
                            (object) ['nick' => 'test.*.*', 'ignore' => false],
                            (object) ['nick' => 'web.service.updated', 'ignore' => false],
                        ];
                    }
                };
            }
        });

        $event->subscribe('test.*.*');

        // equals
        $this->assertTrue($event->exists('web.service.updated'));
        $this->assertFalse($event->exists('guard.session.created'));

        // wildcard
        $this->assertTrue($event->exists('test.post.updated'));

        // wildcard
        $this->assertTrue($event->match('test.post.created', '*.post.*'));
        $this->assertFalse($event->match('test.post.created', '*.posts.*'));
    }

    public function testEntityTriggers()
    {
        $bazyaba = $this->create('post', ['text' => 'bazyaba']);

        // afterCreate was triggered
        $this->assertSame($bazyaba->text, 'bazyaba!');

        $this->dispatch('module.trigger', [
            'space' => 'post',
            'id' => $bazyaba->id,
            'type' => 'create',
        ]);

        // afterCreate + afterUpdate
        $this->assertSame($bazyaba->text, 'bazyaba!!.');

        $bazyaba->text = 'test';
        $bazyaba->save();

        $this->assertSame($bazyaba->text, 'test.');

        $this->dispatch('module.trigger', [
            'space' => 'post',
            'id' => $bazyaba->id,
            'type' => 'update',
        ]);

        $this->assertSame($bazyaba->text, 'test..');
    }
}
