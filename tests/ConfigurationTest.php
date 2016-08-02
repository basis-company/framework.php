<?php

use Basis\Config;

class ConfigurationTest extends TestSuite
{
    function testConfiguration()
    {
        $config = $this->app->get(Config::class);
        $this->assertInstanceOf(Config::class, $config);

        $this->assertSame($config['administrator.name'], 'nekufa');
        $this->assertSame($config['administrator'], ['name' => 'nekufa']);

        $config['administrator.email'] = 'nekufa@gmail.com';

        $this->assertSame($config['administrator'], [
            'name' => 'nekufa',
            'email' => 'nekufa@gmail.com',
        ]);
    }
}