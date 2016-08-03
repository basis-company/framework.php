<?php

use Basis\Http;

class HttpTest extends TestSuite
{
    function test()
    {
        $http = $this->app->get(Http::class);

        // index method
        $this->assertSame(['index', 'index'], $http->getChain('/'));
        $this->assertSame(['hello', 'index'], $http->getChain('/hello/'));
        $this->assertSame(['hello', 'index'], $http->getChain('/hello/////'));
        $this->assertSame(['hello', 'nekufa'], $http->getChain('/hello/nekufa'));

        // index routing
        $result = [
            $http->process("/"),
            $http->process("/index"),
            $http->process("/index/index"),
        ];

        $this->assertSame($result[0], 'index page');
        $this->assertSame($result[1], 'index page');
        $this->assertSame($result[2], 'index page');
    }
}