<?php

namespace Test;

use Basis\Converter;
use Basis\Event;
use Basis\Test;

class EventTest extends Test
{
    public $mocks = [
        ['event.fire', [], 'fireEvent']
    ];

    private $firedEvents = [];

    public function fireEvent($params)
    {
        $this->firedEvents[] = $params;
    }

    public function testEventFire()
    {
        $this->get(Event::class)->fire('person.authorized', ['name' => 'nekufa']);
        $this->assertCount(1, $this->firedEvents);
        $this->assertSame($this->firedEvents[0]->event, 'test.person.authorized');
        $this->assertSame(get_object_vars($this->firedEvents[0]->context), ['name' => 'nekufa']);
    }

    public function testSubscription()
    {
        $event = $this->get(Event::class);
        $subscription = $event->getSubscription();

        $this->assertArrayHasKey('person.created', $subscription);
        $this->assertSame($subscription['person.created'], ['CreateLogin']);
    }
}
