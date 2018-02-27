<?php

namespace Test;

use Basis\Test;

class VersionTest extends Test
{
    public function test()
    {
        $version = get_object_vars($this->dispatch('module.version')->version);
        $this->assertSame($version, ['php' => PHP_VERSION]);

        copy(dirname(getcwd()).'/composer.lock', getcwd().'/composer.lock');
        $version = get_object_vars($this->dispatch('module.version')->version);
        unlink(getcwd().'/composer.lock');
        $this->assertArrayHasKey('tarantool/mapper', $version);
    }
}
