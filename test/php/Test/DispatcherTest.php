<?php

namespace Test;

use Basis\Cache;
use Basis\Test;
use Carbon\Carbon;
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
        $this->app->getContainer()->share(Client::class, $client);

        $mock->append(
            new Response(200, [], json_encode([
                'success' => true,
                'data' => ['mocked' => true]
            ]))
        );

        $result = $this->dispatch('service.hello');
        $this->assertTrue($result->mocked);
    }

    public function testCaching()
    {
        $this->mock('say.hello')->willReturn(function ($params) {
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
    }

    public function testJobOverwrite()
    {
        $result = $this->dispatch('module.contents');
        $this->assertSame($result->message, 'ok');
    }
}
