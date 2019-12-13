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
            if ($k == 'parent') {
                $v = (object) $v;
            }
            $this->$k = $v;
        }

        return $this;
    }

    public function getPerson()
    {
        return $this->parent ? $this->parent->person : $this->person;
    }

    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $k => $v) {
            if (!is_null($v)) {
                if ($k == 'parent') {
                    $result[$k] = [];
                    foreach ($v as $kk => $vv) {
                        if ($vv) {
                            $result[$k][$kk] = $vv;
                        }
                    }
                    if (!count($result[$k])) {
                        unset($result[$k]);
                    }
                } else {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }
}
