<?php

namespace Basis\Jobs\Service;

/**
 * Get Metrics information
 */
class Metrics
{
    public function run()
    {
        return [
            'memory_usage' => memory_get_usage(),
            'memory_usage_real' => memory_get_usage(true),
            'memory_peak_usage' => memory_get_peak_usage(),
            'memory_peak_usage_real' => memory_get_peak_usage(true),
        ];
    }
}