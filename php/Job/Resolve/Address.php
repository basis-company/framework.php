<?php

namespace Basis\Job\Resolve;

use Basis\Toolkit;

class Address
{
    use Toolkit;

    public string $name;
    public ?int $cache = null;

    public function run()
    {
        if ($this->cache === null) {
            $this->cache = getenv('BASIS_RESOLVE_ADDRESS_CACHE') ?: 60;
        }
        if ($this->name === null) {
            throw new Exception("Name should be defined");
        }

        $host = $this->name;

        if (getenv('BASIS_ENVIRONMENT') !== 'dev') {
            $host = getenv(strtoupper(str_replace('-', '_', $this->name)) . '_SERVICE_HOST');
            if (!$host) {
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
