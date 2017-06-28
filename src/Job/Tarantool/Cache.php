<?php

namespace Basis\Job\Tarantool;

use Basis\Filesystem;
use Tarantool\Mapper\Mapper;

class Cache
{
    public function run(Mapper $mapper, Filesystem $fs)
    {
        $filename = $fs->getPath('.cache/mapper-meta.php');

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir);
            chown($dir, 'www-data');
            chgrp($dir, 'www-data');
        }

        file_put_contents($filename, '<?php return '.var_export($mapper->getMeta(), true).';');
        chown($filename, 'www-data');
        chgrp($filename, 'www-data');
    }
}
