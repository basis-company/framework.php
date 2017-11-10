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
    'etcd' => function() {
        $host = getenv('ETCD_SERVICE_HOST') ?: 'etcd';
        $port = getenv('ETCD_SERVICE_PORT') ?: 2379;
        return [
            'connection' => "http://$host:$port"
        ];
    },
    'service' => $service,
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
