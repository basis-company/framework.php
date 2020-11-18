<?php

namespace Basis;

use Carbon\Carbon;

class Context
{
    public $channel;
    public $ip;

    public $session;

    public $company;
    public $person;
    public $module;

    public $parent;
    public $event;

    public function execute($context, $callback)
    {
        $origin = $this->toArray();

        $this->reset($context);
        $result = call_user_func($callback);
        $this->reset($origin);

        return $result;
    }

    public function reset($context = []): self
    {
        foreach ($this as $k => $_) {
            $this->$k = null;
        }
        $this->apply($context);

        return $this;
    }

    public function apply($data): self
    {
        foreach ($data as $k => $v) {
            if ($k == 'parent' && $v) {
                $v = (object) $v;
            }
            $this->$k = $v;
        }

        return $this;
    }

    public function getPerson()
    {
        return $this->parent && $this->parent->person ? $this->parent->person : $this->person;
    }

    public function toArray(): array
    {
        static $converter;
        if (!$converter) {
            $converter = new Converter();
        }

        return $converter->toArray($this);
    }
}
