<?php

namespace Basis\Jobs\Generate;

use Basis\Framework;
use Basis\Filesystem;

/**
 * Generate tarantool migration
 */
class Migration
{
    public $name;

    public function run(Filesystem $filesystem, Framework $framework)
    {
        $time = time();
        $namespace = date('FY', $time);
        $date = date('Ymd_His_', $time);

        if(!is_array($this->name)) {
            $this->name = explode(' ', $this->name);
        }

        $class = '';
        foreach($this->name as $piece) {
            $class .= ucfirst($piece);
        }

        $template = $framework->getPath('resources/templates/migration.php');

        ob_start();
        include($template);
        $contents = ob_get_clean();

        $path = $filesystem->getPath('resources/migrations');
        if(!is_dir($path)) {
            mkdir($path);
        }

        $path = $filesystem->getPath('resources/migrations/'.date('Ym', $time));
        if(!is_dir($path)) {
            mkdir($path);
        }

        $filename = $path.'/'.$date.$class.'.php';
        file_put_contents($filename, $contents);

        return compact('filename', 'namespace', 'class');
    }
}
