<?php

namespace Basis\Feedback;

use Basis\Job;
use Exception;

abstract class Feedback extends Exception
{
    abstract public function getHashes(): array;
    abstract public function getValue(string $hash);

    public function process(Job $job)
    {
        $confirmations = [];

        if (property_exists($job, '_confirmations')) {
            $confirmations = $job->_confirmations;
        }

        foreach ($this->getHashes() as $hash) {
            if (in_array($hash, $confirmations)) {
                return $this->getValue($hash);
            }
        }

        throw $this;
    }
}
