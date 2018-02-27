<?php

namespace Job;

use Basis\Job;

class Person extends Job
{
    public $session;

    public function run()
    {
        return ['person' => $this->session->person];
    }
}
