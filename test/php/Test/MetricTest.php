<?php

namespace Test;

use Basis\Test;
use Basis\Metric\Uptime;

class MetricTest extends Test
{
    public function testNullableValues()
    {
        $this->assertNull($this->get(Uptime::class)->getValue());
    }

    public function testBasics()
    {
        $uptime = $this->get(Uptime::class);
        $uptime->update(time() - 30);

        $this->assertEquals($uptime->getValue(), 30);
    }
}
