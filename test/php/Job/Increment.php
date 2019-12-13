<?php

namespace Job;

use Basis\Job;

class Increment extends Job
{
    public $note;

    public function run()
    {
        $this->require('note');

        $note = $this->findOrFail('note', $this->note);
        $note->message = ($note->message ?: 0) + 1;
        $note->save();

        return compact('note');
    }
}
