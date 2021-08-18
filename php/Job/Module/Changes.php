<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Telemetry\Metrics\Operations;
use Basis\Telemetry\Tracing\Tracer;

class Changes
{
    public string $producer;

    public function __construct(
        private Event $event,
    ) {
    }

    public function run()
    {
        if ($this->event->hasChanges()) {
            $this->event->fireChanges($this->producer);
        }
    }
}
