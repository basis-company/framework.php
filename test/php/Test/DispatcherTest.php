<?php

namespace Test;

use Carbon\Carbon;
use Basis\Test;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class DispatcherTest extends Test
{
    public $disableRemote = false;

    public function test()
    {
        $container = [];
        $mock = new MockHandler();

        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);
        $this->app->share(Client::class, $client);

        $mock->append(
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['mocked' => true]
            ]))
        );

        $result = $this->app->dispatch('service.hello');

        $mock->append(
            new Response(200, [], json_encode([
                'success' => true,
                'data' => [
                    ['message' => 'hello, nekufa'],
                    ['message' => 'hello, rybakit'],
                ]
            ]))
        );

        $results = $this->app->dispatch('service.hello', [['name' => 'nekufa'], ['name' => 'rybakit']]);
        $this->assertCount(2, $results);

        $this->assertCount(2, $container);
        $body = json_encode([
            'job' => 'service.hello',
            'params' => [
                ['name' => 'nekufa'],
                ['name' => 'rybakit']
            ]
        ]);

        $this->assertNotEquals(-1, strpos($container[1]['request']->getBody(), $body));
    }

    public function testExpire()
    {
        $this->mock('say.hello')->willReturn(function($params) {
            return [
                'msg' => 'Hello, ' . $params['name'],
                'hash' => md5(microtime(1)),
                'expire' => Carbon::now()->addHour()->getTimestamp(),
            ];
        });

        $nekufa1 = $this->dispatch('say.hello', ['name' => 'nekufa']);
        $nekufa2 = $this->dispatch('say.hello', ['name' => 'nekufa']);
        $vasya1 = $this->dispatch('say.hello', ['name' => 'vasya']);
        $this->assertSame($nekufa1->hash, $nekufa2->hash);
        $this->assertNotSame($nekufa1->hash, $vasya1->hash);

        Carbon::setTestNow(Carbon::now()->addDay());
        $nekufa3 = $this->dispatch('say.hello', ['name' => 'nekufa']);
        $this->assertNotSame($nekufa1->hash, $nekufa3->hash);
    }

    public function tearDown()
    {
        parent::tearDown();
        if (is_dir('.cache')) {
            foreach (scandir('.cache') as $f) {
                if ($f != '.' && $f != '..') {
                    unlink('.cache/'.$f);
                }
            }
            rmdir('.cache');
        }
    }
}
