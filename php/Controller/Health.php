<?php

namespace Basis\Controller;

use Basis\Metric\BackgroundHold;

class Health
{
    public function index(BackgroundHold $hold)
    {
        // 5 minutes default limit
        $threshold = getenv('BACKGROUND_HOLD_MAX') ?: 5 * 60;

        // validate current hold value
        if ($hold->getValue() >= $threshold) {
            return;
        }

        // all is correct
        return 'ok';
    }
}
