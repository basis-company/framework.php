<?php

namespace Test;

use Basis\Test;
use Job\Actor;

class HousekeepingTest extends Test
{
    public function test()
    {
        $instance = $this->get(Actor::class);
        $this->assertSame($instance, $this->get(Actor::class));

        $this->dispatch('module.housekeeping');
        $this->assertNotSame($instance, $this->get(Actor::class));
    }
}
