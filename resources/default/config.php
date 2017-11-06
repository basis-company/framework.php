<?php

return [
    'etcd' => call_user_func(function() {
        $host = getenv('ETCD_SERVICE_HOST') ?: 'etcd';
        $port = getenv('ETCD_SERVICE_PORT') ?: 2379;

        return [
            'connection' => "http://$host:$port"
        ];
    }),
    'service' => call_user_func(function() {
        $service = getenv('SERVICE_NAME');

        if (!$service) {
            $service = dirname(getcwd());
            if ($service === 'html') {
                throw new Exception("SERVICE_NAME environment not defined");
            }
        }
        return $service;
    }),
    'tarantool' => call_user_func(function() {
        $host = getenv('TARANTOOL_SERVICE_HOST') ?? $service.'-db';
        $port = getenv('TARANTOOL_SERVICE_PORT') ?? '3301';
        $connection = "tcp://$host:$port";

        $mapping = [
            'connect_timeout' => 'TARANTOOL_CONNECT_TIMEOUT',
            'socket_timeout' => 'TARANTOOL_SOCKET_TIMEOUT',
            'tcp_nodelay' => 'TARANTOOL_TCP_NODELAY',
        ];

        $params = [];
        foreach ($mapping as $param => $name) {
            if (getenv($name)) {
                $params[$param] = getenv($name);
            }
        }
        return [
            'connection' => $connection,
            'params' => $params,
        ];

    }),
];
