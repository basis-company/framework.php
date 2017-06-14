<?php

namespace Basis\Job\Generate;

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
        $created_at = date('Y-m-d H:i:s', $time);

        if (!is_array($this->name)) {
            $this->name = explode(' ', $this->name);
        }

        $class = '';
        foreach ($this->name as $piece) {
            $class .= ucfirst($piece);
        }

        $template = $framework->getPath('resources/templates/migration.php');

        ob_start();
        include($template);
        $contents = ob_get_clean();

        $path = $filesystem->getPath('php/Migration');
        if (!is_dir($path)) {
            mkdir($path);
        }

        $path = $filesystem->getPath('php/Migration/'.$namespace);
        if (!is_dir($path)) {
            mkdir($path);
        }

        $filename = 'php/Migration/'.$namespace.'/'.$class.'.php';
        file_put_contents($filename, $contents);

        return compact('filename', 'namespace', 'class');
    }
}
