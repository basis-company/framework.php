<?php

namespace Test;

use Basis\Test;
use Basis\Test\Storage as TestStorage;
use Basis\Storage;

class StorageTest extends Test
{
    public function testUsage()
    {
        $filename = 'greet.txt';
        $contents = 'Hello all!';
        $hash = $this->upload($filename, $contents);
        $this->assertSame($this->download($hash), $contents);
        $this->assertSame($this->get(Storage::class)->url($hash), "/tmp/$hash");
    }

    public function testRegistration()
    {
        $this->assertInstanceOf(Storage::class, $this->get(Storage::class));
        $this->assertInstanceOf(TestStorage::class, $this->get(Storage::class));
        $this->assertInstanceOf(Storage::class, $this->get(TestStorage::class));
    }
}
