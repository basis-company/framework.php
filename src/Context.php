<?php

namespace Basis;

use Carbon\Carbon;

class Context
{
    public $session;
    public $host;
    public $socket;

    public $gateway;
    public $gatewaySocket;

    public $company;
    public $module;
    public $person;

    public $parent;

    public function apply($data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
}
