<?php

namespace Basis\Job\Tarantool;

use Basis\Filesystem;
use Tarantool\Mapper\Mapper;

class Cache
{
    public function run(Mapper $mapper, Filesystem $fs)
    {
        $filename = $fs->getPath('.cache/mapper-meta.php');
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename));
        }

        file_put_contents($filename, '<?php return '.var_export($mapper->getMeta()).';');
    }
}
