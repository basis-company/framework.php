<?php

use Basis\Filesystem;

class FilesystemTest extends TestSuite
{
    function testBasics()
    {
        $filesystem = $this->app->get(Filesystem::class);

        $this->assertSame($filesystem->getPath(), __DIR__.DIRECTORY_SEPARATOR.'example');
        $this->assertSame($filesystem->getPath(''), __DIR__.DIRECTORY_SEPARATOR.'example');

        $this->assertSame($filesystem->getPath('config'), implode(DIRECTORY_SEPARATOR, [__DIR__, 'example', 'config']));

        $this->assertSame(
            $filesystem->getPath('config', 'administrator.php'),
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'example', 'config', 'administrator.php'])
        );

        $this->assertSame($filesystem->getPath(
            'config'.DIRECTORY_SEPARATOR.'administrator.php'),
            implode(DIRECTORY_SEPARATOR, [__DIR__, 'example', 'config', 'administrator.php'])
        );

        $this->assertTrue($filesystem->exists('resources', 'config'));
        $this->assertTrue($filesystem->exists('resources/config/administrator.php'));
        $this->assertFalse($filesystem->exists('resources/config/not-exists.php'));
    }
}