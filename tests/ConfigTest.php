<?php

use Basis\Config;

class ConfigTest extends TestSuite
{
    public function test()
    {
        $config = $this->app->get(Config::class);
        $this->assertInstanceOf(Config::class, $config);

        $this->assertSame($config, $this->app->get(Config::class));

        $this->assertSame($config['service'], 'example');
        $this->assertSame($config['environment'], 'testing');
        $this->assertSame($config['etcd.connection'], 'http://etcd:2379');
        $this->assertCount(4, get_object_vars($config));
    }
}
