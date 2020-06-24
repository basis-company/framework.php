<?php

namespace Basis\Test;

use Basis\Storage as BaseStorage;

class Storage extends BaseStorage
{
    public function download(string $hash)
    {
        return $this->data[$hash];
    }

    public function upload(string $filename, $contents): string
    {
        $hash = md5($contents);
        $this->data[$hash] = $contents;
        return $hash;
    }

    public function url(string $hash): string
    {
        return "/tmp/$hash";
    }
}
