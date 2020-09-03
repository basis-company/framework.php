<?php

namespace Test;

use Basis\Test;
use Basis\Metric\Uptime;
use Basis\Metric\Registry;

class MetricTest extends Test
{
    public function testNullableValues()
    {
        $this->assertNull($this->get(Uptime::class)->getValue());
    }

    public function testRendering()
    {
        $uptime = $this->get(Uptime::class);
        $uptime->update(time() - 30);
        $result = $this->get(Registry::class)->render();
        $this->assertStringContainsString("uptime 30", $result);
    }

    public function testBasics()
    {
        $uptime = $this->get(Uptime::class);
        $uptime->update(time() - 30);

        $this->assertEquals($uptime->getValue(), 30);
    }
}
