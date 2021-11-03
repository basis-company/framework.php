<?php

namespace Test;

use Basis\Context;
use Basis\Test;

class ActorTest extends Test
{
    public function testBasics()
    {
        $context = $this->get(Context::class);

        $this->assertSame($context, $this->actAs(1));
        $this->assertSame($context->person, 1);
        $this->assertSame($context->module, null);

        $this->assertSame($context, $this->actAs(2));
        $this->assertSame($context->person, 2);
        $this->assertSame($context->module, null);

        // configured context
        $this->actAs(['person' => 3, 'module' => 1 ]);
        $this->assertSame($context->person, 3);
        $this->assertSame($context->module, 1);

        // context reset on actor reconfigure
        $this->actAs(4);
        $this->assertSame($context->person, 4);
        $this->assertSame($context->module, null);
    }
}
