<?php

namespace Test;

use Basis\Test;

class DefaultArtifactsTest extends Test
{
    public function test()
    {
        $this->assertFileExists(getcwd().'/.htaccess');
        $this->assertFileExists(getcwd().'/server.php');

        unlink(getcwd().'/.htaccess');
        unlink(getcwd().'/server.php');
        $this->assertFileNotExists(getcwd().'/.htaccess');
        $this->assertFileNotExists(getcwd().'/server.php');

        $this->dispatch('module.defaults');
        $this->assertFileExists(getcwd().'/.htaccess');
        $this->assertFileExists(getcwd().'/server.php');
    }
}
