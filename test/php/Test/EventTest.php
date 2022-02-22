<?php

namespace Test;

use Basis\Converter;
use Basis\Event;
use Basis\Http;
use Basis\Test;

class EventTest extends Test
{
    public $mocks = [
        [ 'event.fire', [], 'fireEvent' ],
        [ 'event.changes', [], 'fireEvent' ],
    ];

    private $firedEvents = [];

    public function fireEvent($params)
    {
        $this->firedEvents[] = $params;
    }

    public function testChangesCall()
    {
        $this->dispatch('module.changes', [
            'producer' => 'tester',
        ]);
        $this->assertTrue(true);
    }

    public function testEventFire()
    {
        $this->get(Event::class)->fire('person.authorized', ['name' => 'nekufa']);
        $this->dispatch('module.execute');
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

        $this->assertArrayHasKey('person.person.*', $subscription);
        $this->assertSame($subscription['person.person.*'], ['CreateLogin']);
    }

    public function testProcessing()
    {
        $response = $this->dispatch('module.handle', [
            'event' => 'person.created',
            'context' => (object) [ 'name' => 'nekufa' ],
        ]);

        $this->assertNotNull($response->data->CreateLogin);

        $this->assertSame($response->data->CreateLogin->msg, 'person was created with name nekufa');

        $response = $this->dispatch('module.handle', [
            'event' => 'person.person.updated',
            'context' => (object) [ 'name' => 'dmitry' ],
        ]);

        $this->assertNotNull($response->data->CreateLogin);
        $this->assertSame($response->data->CreateLogin->msg, 'person.person was updated with name dmitry');
    }
}
