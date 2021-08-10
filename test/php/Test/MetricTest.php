<?php

namespace Test;

use Basis\Metric\BackgroundHold;
use Basis\Metric\Registry;
use Basis\Metric\StartTime;
use Basis\Metric\Uptime;
use Basis\Test;

class MetricTest extends Test
{
    public function testNullableValues()
    {
        $this->assertNull($this->get(Uptime::class)->getValue());
    }

    public function testRendering()
    {
        $uptime = $this->get(StartTime::class);
        $uptime->setValue(time() - 30);
        $result = $this->get(Registry::class)->render();
        $this->assertStringContainsString("uptime 30", $result);
    }

    public function testLabelRendering()
    {
        $uptime = $this->get(BackgroundHold::class);
        $uptime->setValue(5, [ 'category' => 'executor' ]);
        $uptime->setValue(8, [ 'category' => 'integration' ]);
        $uptime->setValue(13, [ 'cat' => 'share', 'ns' => 'test' ]);
        $result = $this->get(Registry::class)->render();
        $this->assertStringContainsString('background_hold{category="executor"} 5', $result);
        $this->assertStringContainsString('background_hold{category="integration"} 8', $result);
        $this->assertStringContainsString('background_hold{cat="share",ns="test"} 13', $result);
    }

    public function testBasics()
    {
        $uptime = $this->get(Uptime::class);
        $uptime->setValue(30);

        $this->assertEquals($uptime->getValue(), 30);
    }
}
