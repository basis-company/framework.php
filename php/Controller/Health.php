<?php

namespace Basis\Controller;

use Basis\Metric\BackgroundHold;
use Nyholm\Psr7\Response;

class Health
{
    public function index(BackgroundHold $hold)
    {
        // 5 minutes default limit
        $threshold = getenv('BACKGROUND_HOLD_MAX') ?: 5 * 60;
        
        // validate current hold value
        if ($hold->getValue() >= $threshold) {
            return $this->failure();
        }

        // all is correct
        return $this->ok();
    }

    public function failure()
    {
        return new Response(503);
    }

    public function ok()
    {
        return new Response(200, [], 'ok');
    }
}
