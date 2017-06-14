<?php

$service = getenv('SERVICE_NAME');

if (!$service) {
    throw new Exception("SERVICE_NAME environment not defined");
}

return [
    'service' => $service,
    'tarantool' => 'tcp://'.$service.'-db:3301',
];
