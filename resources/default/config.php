<?php

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
    'clickhouse' => function() use ($service) {
        return [
            'host' => getenv('CLICKHOUSE_HOST') ?: $service.'-ch',
            'port' => getenv('CLICKHOUSE_PORT') ?: '8123',
            'username' => getenv('CLICKHOUSE_USERNAME') ?: 'default',
            'password' => getenv('CLICKHOUSE_PASSWORD') ?: '',
        ];
    },
    'tarantool' => function() use ($service) {
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
        return [
            'connection' => getenv('TARANTOOL_CONNECTION') ?: 'tcp://'.$service.'-db:3301',
            'params' => $params,
        ];
    },
];
