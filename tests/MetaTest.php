<?php

use Basis\Job\Job\Info;
use Basis\Runner;
use Job\Hello;

class MetaTest extends TestSuite
{
    public function test()
    {
        $meta = $this->app->dispatch('module.assets');

        $this->assertArrayHasKey('js', $meta);
        $this->assertArrayHasKey('test.js', $meta['js']);
        $this->assertArrayHasKey('file.styl', $meta['styl']);
    }
}
