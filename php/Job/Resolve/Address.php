<?php

namespace Basis\Job\Resolve;

use Basis\Toolkit;

class Address
{
    use Toolkit;

    public ?string $name = null;
    public int $cache = 60;

    public function run()
    {
        $host = $this->name;
        if (getenv('BASIS_ENVIRONMENT') !== 'dev') {
            $host = gethostbyname($this->name);
        }

        return [
            'host' => $host,
            'expire' => time() + 60 * $this->cache,
        ];
    }
}
