<?php

namespace Test;

use Basis\Test;

class VersionTest extends Test
{
    public function test()
    {
        $version = get_object_vars($this->dispatch('module.version')->version);
        $this->assertEquals($version, ['php' => PHP_VERSION, 'service' => null]);

        copy(dirname(getcwd()).'/composer.lock', getcwd().'/composer.lock');
        $version = get_object_vars($this->dispatch('module.version')->version);
        unlink(getcwd().'/composer.lock');
        $this->assertArrayHasKey('league/container', $version);
    }
}
