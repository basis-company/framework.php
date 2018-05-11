<?php

use Basis\Service;

$service = getenv('SERVICE_NAME');
if (!$service) {
    $service = dirname(getcwd());
    if ($service === 'html') {
        throw new Exception("SERVICE_NAME environment not defined");
    }
}

return [
    'environment' => getenv('SERVICE_ENVIRONMENT') ?? 'production',
    'service' => $service,
    'clickhouse' => function($app) use ($service) {
        $host = getenv('CLICKHOUSE_HOST');
        if (!$host) {
            $host = $app->get(Service::class)->getHost($service.'-ch')->address;
        }
        return [
            'host' => $host,
            'port' => getenv('CLICKHOUSE_PORT') ?: '8123',
            'username' => getenv('CLICKHOUSE_USERNAME') ?: 'default',
            'password' => getenv('CLICKHOUSE_PASSWORD') ?: '',
        ];
    },
    'tarantool' => function($app) use ($service) {
        $params = [];
        $mapping = [
            'connect_timeout' => 'TARANTOOL_CONNECT_TIMEOUT',
            'socket_timeout'  => 'TARANTOOL_SOCKET_TIMEOUT',
            'tcp_nodelay'     => 'TARANTOOL_TCP_NODELAY',
        ];
        foreach ($mapping as $param => $name) {
            if (getenv($name)) {
                $params[$param] = getenv($name);
            }
        }
        $connection = getenv('TARANTOOL_CONNECTION');
        if (!$connection) {
            $host = $app->get(Service::class)->getHost($service.'-db')->address;
            $connection = 'tcp://'.$host.':3301';
        }
        return [
            'connection' => $connection,
            'params' => $params,
        ];
    },
];
