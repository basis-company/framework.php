<?php

namespace Test;

use Basis\Converter;
use Basis\Event;

class EventTest extends TestSuite
{
    public function test()
    {
        $event = $this->app->get(Event::class);
        $subscription = $event->getSubscription();

        $this->assertArrayHasKey('person.created', $subscription);
        $this->assertSame($subscription['person.created'], ['CreateLogin']);
    }
}
