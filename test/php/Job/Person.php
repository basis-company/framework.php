<?php

namespace Job;

use Basis\Job;

class Person extends Job
{
    public ?object $session = null;

    public function run()
    {
        return ['person' => $this->session->person];
    }
}
