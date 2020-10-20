<?php

namespace Basis\Configuration;

use Amp\Http\Server\HttpServer;
use Amp\Socket\Server as Socket;
use Basis\Container;
use Basis\Http;
use Psr\Log\NullLogger;

class Server
{
    public function init(Container $container)
    {
        $container->share(HttpServer::class, function () use ($container) {
            $sockets = [
                Socket::listen("0.0.0.0:80"),
                Socket::listen("[::]:80"),
            ];

            $router = $container->get(Http::class)->getRouter();
            $logger = new NullLogger();

            return new HttpServer($sockets, $router, $logger);
        });
    }
}
