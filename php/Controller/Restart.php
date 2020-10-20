<?php

namespace Basis\Controller;

use Amp\Loop;

class Restart
{
    public function index()
    {
        Loop::delay(1, function () {
            exec('kill `pgrep starter`');
        });

        return 'ok';
    }
}
