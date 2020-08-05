<?php

namespace Basis\Controller;

class Restart
{
    public function index()
    {
        exec('kill `pgrep starter`')
    }
}
