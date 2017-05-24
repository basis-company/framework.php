<?php

namespace Basis\Job\Module;

use Basis\Filesystem;

class Assets
{
    private $fs;
    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }
    public function run()
    {
        $artifacts = [
            'js' => $this->map('js'),
            'styl' => $this->map('styl'),
        ];

        $artifacts['hash'] = md5(json_encode($artifacts));
        return $artifacts;
    }

    public function map($type)
    {
        $mapping = [];
        if (is_dir($type)) {
            exec('find '.$type.' -name "*.'.$type.'" -exec md5sum {} \; | sort', $contents);
            foreach ($contents as $i => $row) {
                list($hash, $file) = explode("  ", $row);
                $file = substr($file, strlen($type) + 1);
                $mapping[$file] = $hash;
            }
        }
        return $mapping;
    }
}
