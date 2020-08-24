<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server;

class Swoole
{
    public function init(Container $container)
    {
        $container->share(Server::class, function () use ($container) {

            $host = getenv('SWOOLE_HTTP_SERVER_HOST') ?: "0.0.0.0";
            $port = getenv('SWOOLE_HTTP_SERVER_PORT') ?: "80";

            $server = new Server($host, $port);
            $server->set([
                'buffer_output_size' => getenv('SWOOLE_HTTP_SERVER_BUFFER_OUTPUT_SIZE') ?: 128 * 1024 * 1024,
                'document_root' => getcwd(),
                'enable_coroutine' => true,
                'enable_static_handler' => true,
                'http_compression' => false,
                'dispatch_mode' => getenv('SWOOLE_HTTP_SERVER_DISPATCH_MODE') ?: 3,
                'http_parse_post' => true,
                'max_request' => getenv('SWOOLE_HTTP_SERVER_MAX_REQUEST') ?: 64,
                'worker_num' => getenv('SWOOLE_HTTP_SERVER_WORKER_NUM') ?: 4,
            ]);

            $server->on("start", function () use ($container, $host, $port) {
                $container->get(LoggerInterface::class)
                    ->info([
                        'message' => 'server started',
                        'url' => "http://$host:$port",
                    ]);
            });

            $server->on("shutdown", function () use ($container) {
                $container->get(LoggerInterface::class)
                    ->info([
                        'message' => 'server stopped',
                    ]);
            });

            return $server;
        });
    }
}
