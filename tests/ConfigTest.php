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
        $this->assertCount(3, get_object_vars($config));
    }
}
