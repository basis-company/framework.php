<?php

$service = getenv('SERVICE_NAME');

if (!$service) {
    $service = dirname(getcwd());
    if ($service === 'html') {
        throw new Exception("SERVICE_NAME environment not defined");
    }
}

$host = getenv('TARANTOOL_SERVICE_HOST') ?? $service.'-db';
$port = getenv('TARANTOOL_SERVICE_PORT') ?? '3301';

$tarantool = [
    'connection' => "tcp://$host:$port",
    'params' => [],
];

$mapping = [
    'connect_timeout' => 'TARANTOOL_CONNECT_TIMEOUT',
    'socket_timeout' => 'TARANTOOL_SOCKET_TIMEOUT',
    'tcp_nodelay' => 'TARANTOOL_TCP_NODELAY',
];

foreach ($mapping as $param => $name) {
    if (getenv($name)) {
        $tarantool['params'][$param] = getenv($name);
    }
}

return [
    'service' => $service,
    'tarantool' => $tarantool,
];
