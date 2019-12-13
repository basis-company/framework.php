<?php

namespace Job;

use Basis\Job;
use Basis\Context;

class Actor extends Job
{
    public $note;

    public function run()
    {
        $this->require('note');

        $note = $this->findOrFail('note', $this->note);
        $note->message = $this->get(Context::class)->getPerson() ?: '';
        $note->save();

        return compact('note');
    }
}
