<?php

namespace Test;

use Basis\Test;

class VersionTest extends Test
{
    public function test()
    {
        $version = get_object_vars($this->dispatch('module.version')->version);
        $this->assertEquals($version, ['php' => PHP_VERSION, 'service' => null]);
    }
}
