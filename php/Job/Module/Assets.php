<?php

namespace Basis\Job\Module;

class Assets
{
    public function run()
    {
        $artifacts = [
            'js' => $this->collect('js'),
            'php' => $this->collect('php'),
            'styl' => $this->collect('styl'),
        ];

        return array_merge($artifacts, [
            'hash' => md5(json_encode($artifacts)),
        ]);
    }

    private function collect(string $type): array
    {
        $mapping = [];
        if (is_dir($type)) {
            exec('find ' . $type . ' -name "*.' . $type . '" -exec md5sum {} \; | sort', $contents);
            if ($contents !== null) {
                foreach ($contents as $i => $row) {
                    list($hash, $file) = explode("  ", $row);
                    $file = substr($file, strlen($type) + 1);
                    $mapping[$file] = $hash;
                }
            }
        }
        return $mapping;
    }
}
