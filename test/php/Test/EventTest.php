<?php

namespace Test;

use Basis\Converter;
use Basis\Event;
use Basis\Http;
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

        $this->assertArrayHasKey('person.person.created', $subscription);
        $this->assertSame($subscription['person.person.created'], ['CreateLogin']);
    }

    public function testProcessing()
    {
        $_REQUEST = array_merge($_REQUEST, [
            'event' => 'person.created',
            'context' => json_encode([ 'name' => 'nekufa' ]),
        ]);

        $result = $this->get(Http::class)->process('/event');
        $response = json_decode($result);

        $this->assertTrue($response->success);
        $this->assertNotNull($response->data->CreateLogin);

        $this->assertSame($response->data->CreateLogin->msg, 'person was created with name nekufa');

        $_REQUEST = array_merge($_REQUEST, [
            'event' => 'person.person.created',
            'context' => json_encode([ 'name' => 'nekufa' ]),
        ]);

        $result = $this->get(Http::class)->process('/event');
        $response = json_decode($result);

        $this->assertTrue($response->success);
        $this->assertNotNull($response->data->CreateLogin);

        $this->assertSame($response->data->CreateLogin->msg, 'person.person was created with name nekufa');
    }
}
