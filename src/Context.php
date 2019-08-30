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
    public $event;

    public function reset($context = []) : self
    {
        foreach ($this as $k => $_) {
            $this->$k = null;
        }
        $this->apply($context);

        return $this;
    }

    public function apply($data) : self
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }

        return $this;
    }

    public function getPerson()
    {
        return $this->parent ? $this->parent->person : $this->person;
    }
}
