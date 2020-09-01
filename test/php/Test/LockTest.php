<?php

namespace Test;

use Basis\Lock;
use Basis\Test;
use ReflectionProperty;

class LockTest extends Test
{
    public function testRelease()
    {
        $lock = $this->get(Lock::class);
        $lock->acquire('tester');
        $this->tearDown();

        $this->assertFalse($lock->exists('tester'));
    }

    public function testRelock()
    {
        $lock = $this->get(Lock::class);
        $this->assertFalse($lock->exists('tester'));
        $this->assertTrue($lock->lock('tester'));

        $property = new ReflectionProperty(Lock::class, 'locks');
        $property->setAccessible(true);
        $locks = $property->getValue($lock);
        $property->setValue($lock, []);

        $this->assertFalse($lock->lock('tester'));
    }

    public function test()
    {
        $lock = $this->get(Lock::class);
        $this->assertFalse($lock->exists('tester'));
        $lock->acquire('tester');

        $this->assertTrue($lock->exists('tester'));
    }
}
