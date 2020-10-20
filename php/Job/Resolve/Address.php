<?php

namespace Basis\Job\Resolve;

use Basis\Toolkit;
use Swoole\Coroutine;
use Swoole\Coroutine\System;

class Address
{
    use Toolkit;

    public string $name;
    public int $cache = 60;

    public function run()
    {
        if ($this->name === null) {
            throw new Exception("Name should be defined");
        }

        $host = $this->name;

        if (getenv('BASIS_ENVIRONMENT') !== 'dev') {
            if (class_exists(Coroutine::class) && Coroutine::getContext() !== null) {
                $host = System::gethostbyname($this->name);
            } else {
                $host = gethostbyname($this->name);
            }
            if ($host === false) {
                return [
                    'host' => $this->name,
                ];
            }
        }

        return [
            'host' => $host,
            'expire' => time() + 60 * $this->cache,
        ];
    }
}
