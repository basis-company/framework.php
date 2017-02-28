<?php

use Basis\Http;

use Example\Controllers\Index;

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
        $this->assertSame(['hello', 'nekufa'], $http->getChain('/hello/nekufa?querystring'));

        // class exists
        $this->assertTrue(class_exists(Index::class));
        $this->assertTrue(method_exists(Index::class, 'index'));

        // index routing
        $this->assertSame('index page', $http->process("/"));
        $this->assertSame('index page', $http->process("/index"));
        $this->assertSame('index page', $http->process("/index/index"));

        //
        $this->assertSame('url: ', $http->process("/dynamic"));
        $this->assertSame('url: ', $http->process("/dynamic/"));
        $this->assertSame('url: slug', $http->process("/dynamic/slug"));
        $this->assertSame('url: slug/sub', $http->process("/dynamic/slug/sub"));
    }
}