<?php

namespace Basis\Job\Module;

use Basis\Event;
use Basis\Job;
use Psr\Log\LoggerInterface;

class Changes extends Job
{
    public string $producer;

    public function run(Event $event)
    {
        if ($event->hasChanges()) {
            $event->fireChanges($this->producer);
        }
    }
}
