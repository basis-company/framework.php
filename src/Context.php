<?php

namespace Basis;

use Carbon\Carbon;

class Context
{
    public $access;
    public $channel;
    public $session;

    public $company;
    public $person;
    public $module;

    public $parent;

    public function apply($data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }

    public function getPerson()
    {
        return $this->parent ? $this->parent->person : $this->person;
    }
}
