<?php

namespace Job;

use Basis\Job;

class Person extends Job
{
    public function run()
    {
        return ['person' => $this->session->person];
    }
}
