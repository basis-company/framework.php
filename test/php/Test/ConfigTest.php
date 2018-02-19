<?php

namespace Test;

use Basis\Config;
use Basis\Test;

class ConfigTest extends Test
{
    public function test()
    {
        $config = $this->app->get(Config::class);
        $this->assertInstanceOf(Config::class, $config);

        $this->assertSame($config, $this->app->get(Config::class));

        $this->assertSame($config['service'], 'test');
        $this->assertSame($config['environment'], 'testing');
        $this->assertSame($config['tarantool.connection'], getenv('TARANTOOL_CONNECTION'));
        $this->assertCount(4, get_object_vars($config));

        $this->assertArrayHasKey('service', $config);

        $config['custom.property'] = true;
        $this->assertArrayHasKey('custom', $config);
        $this->assertArrayHasKey('custom.property', $config);

        unset($config['custom']);
        $this->assertNull($config['custom']);
        $this->assertNull($config['custom.property']);
    }
}
