<?php

namespace Basis\Controller;

use Basis\Http;
use Basis\Telemetry\Metrics\Registry;
use Nyholm\Psr7\Response;
use Psr\Log\LoggerInterface;

class Health
{
    public function __construct(
        private Http $http,
        private LoggerInterface $logger,
        private Registry $registry,
    ) {
    }

    public function index()
    {
        $this->http->setLogging(false);

        $backgroundStart = $this->registry->get('background_start');
        if ($backgroundStart) {
            // 5 minutes default limit
            $backgroundHold = $this->registry->get('background_hold');
            $backgroundTimeout = getenv('BACKGROUND_HOLD_MAX') ?: 5 * 60;
            if ($backgroundHold >= $backgroundTimeout) {
                $this->logger->critical('background hold', [
                    'hold' => $backgroundHold,
                    'timeout' => $backgroundTimeout,
                ]);
                return $this->failure();
            }
        }

        $metricsTimeout = getenv('TELEMETRY_METRICS_TIMEOUT') ?: 5;
        $startTime = $this->registry->get('start_time');
        $uptime = $this->registry->get('uptime');
        if ($startTime + $uptime + $metricsTimeout < time()) {
            $this->logger->critical('metrics update timeout', [
                'contact_ago' => time() - $startTime - $uptime,
                'start' => $startTime,
                'timeout' => $metricsTimeout,
                'uptime' => $uptime,
            ]);
            return $this->failure();
        }

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
