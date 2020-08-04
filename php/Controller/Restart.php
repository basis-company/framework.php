<?php

namespace Basis\Controller;

use Swoole\Http\Server;

class Restart
{
    public function index(Server $server)
    {
        opcache_reset();
        $server->reload(true);
    }
}
