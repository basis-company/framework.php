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
                'backlog' => getenv('SWOOLE_HTTP_SERVER_BACKLOG') ?: 64,
                'buffer_output_size' => getenv('SWOOLE_HTTP_SERVER_BUFFER_OUTPUT_SIZE') ?: 128 * 1024 * 1024,
                'discard_timeout_request' => getenv('SWOOLE_HTTP_SERVER_DISCARD_TIMEOUT_REQUEST') !== 'false',
                'dispatch_mode' => getenv('SWOOLE_HTTP_SERVER_DISPATCH_MODE') ?: 1,
                'document_root' => getcwd(),
                'enable_coroutine' => getenv('SWOOLE_HTTP_SERVER_ENABLE_COROUTINE') !== 'false',
                'enable_static_handler' => true,
                'http_compression' => false,
                'http_parse_post' => true,
                'log_level' => getenv('SWOOLE_HTTP_SERVER_LOG_LEVEL') ?: 4,
                'max_request' => getenv('SWOOLE_HTTP_SERVER_MAX_REQUEST') ?: 0,
                'open_http_protocol' => true,
                'reactor_num' => getenv('SWOOLE_HTTP_SERVER_REACTOR_NUM') ?: 2,
                'worker_num' => getenv('SWOOLE_HTTP_SERVER_WORKER_NUM') ?: 2,
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
