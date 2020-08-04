<?php

namespace Test;

use Basis\Lock;
use Basis\Test;

class LockTest extends Test
{
    public function testRelease()
    {
        $lock = $this->get(Lock::class);
        $lock->acquire('tester');
        $this->tearDown();

        $this->assertFalse($lock->exists('tester'));
    }

    public function test()
    {
        $lock = $this->get(Lock::class);
        $this->assertFalse($lock->exists('tester'));
        $lock->acquire('tester');

        $this->assertTrue($lock->exists('tester'));
    }
}
