<?php

use Basis\Config;

class ConfigTest extends TestSuite
{
    public function test()
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
