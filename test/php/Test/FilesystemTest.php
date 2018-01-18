<?php

namespace Test;

use Basis\Filesystem;
use Basis\Test;

class FilesystemTest extends Test
{
    public function test()
    {
        $filesystem = $this->app->get(Filesystem::class);

        $this->assertSame($filesystem->getPath(), getcwd());
        $this->assertSame($filesystem->getPath(''), getcwd());

        $this->assertSame($filesystem->getPath('config'), getcwd().'/config');

        $this->assertSame(
            $filesystem->getPath('config', 'administrator.php'),
            getcwd().'/config/administrator.php'
        );

        $this->assertSame(
            $filesystem->getPath('config'.DIRECTORY_SEPARATOR.'administrator.php'),
            getcwd().'/config/administrator.php'
        );

        $this->assertTrue($filesystem->exists('php', 'BusinessLogic.php'));
        $this->assertFalse($filesystem->exists('php', 'InvalidLogic.php'));
    }
}
