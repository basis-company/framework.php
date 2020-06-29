<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Psr\Log\LoggerInterface;
use Swoole\HTTP\Server;

class Swoole
{
    public function init(Container $container)
    {
        $container->share(Server::class, function () use ($container) {

            $host = getenv('SWOOLE_HTTP_SERVER_HOST') ?: "0.0.0.0";
            $port = getenv('SWOOLE_HTTP_SERVER_PORT') ?: "80";

            $server = new Server($host, $port);
            $server->set([
                'buffer_output_size' => 128 * 1024 * 1024,
                'document_root' => getcwd(),
                'enable_static_handler' => true,
                'http_parse_post' => true,
            ]);

            $server->on("start", function () use ($container, $host, $port) {
                $container->get(LoggerInterface::class)
                    ->info("Swoole http server is started at http://$host:$port");
            });

            $server->on("start", function () use ($container, $host, $port) {
                $container->get(LoggerInterface::class)
                    ->info("Swoole http server is started at http://$host:$port");
            });

            $server->on("shutdown", function () use ($container) {
                $container->get(LoggerInterface::class)
                    ->info("Swoole http server is stopped");
            });

            return $server;
        });
    }
}
