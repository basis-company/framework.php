<?php

return [
    'service' => 'example',
    'tarantool' => 'tcp://'.getenv('TARANTOOL_SERVICE_HOST').':'.getenv('TARANTOOL_SERVICE_PORT'),
];
