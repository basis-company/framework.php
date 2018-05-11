<?php

namespace Test;

use Basis\Test;
use Basis\Filesystem;

class BootstrapTest extends Test
{
    public $mocks = [
        ['event.subscribe', [], 'subscription'],
        ['web.register', [], 'registration'],
    ];

    public function test()
    {
        $this->assertCount(0, $this->subscriptions);
        $this->assertCount(0, $this->registrations);
        $result = $this->dispatch('module.bootstrap');

        // web.register was called
        $this->assertCount(1, $this->registrations);

        // event.subscribe was called
        $this->assertCount(1, $this->subscriptions);
        $this->assertSame($this->subscriptions[0]->event, 'person.created');
        $this->assertSame($this->subscriptions[0]->service, 'test');

        // cache exists
        $this->dispatch('module.bootstrap');
    }


    private $registrations = [];
    public function registration($params)
    {
        $this->registrations[] = $params;
    }

    private $subscriptions = [];
    public function subscription($params)
    {
        $this->subscriptions[] = $params;
    }
}
