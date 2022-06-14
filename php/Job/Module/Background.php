<?php

namespace Basis\Job\Module;

use Basis\Metric\BackgroundStart;
use Basis\Toolkit;
use Psr\Log\LoggerInterface;
use Tarantool\Mapper\Plugin\Spy;
use Throwable;

class Background
{
    use Toolkit;

    public function run(LoggerInterface $logger)
    {
        if (!class_exists(\Job\Background::class)) {
            return [
                'msg' => 'no background job',
            ];
        }

        $this->get(BackgroundStart::class)->update();
        $this->getMapper()->getPlugin(Spy::class)->reset();
        $this->dispatch('module.flush');

        $result = $this->dispatch('module.process', [
            'job' => $this->app->getName() . '.background',
        ]);

        if (property_exists($result, 'success') && !$result->success) {
            if (property_exists($result->result, 'exception')) {
                $logger->error($result->result->exception, [
                    'file' => $result->result->file,
                    'line' => $result->result->line,
                    'trace' => $result->result->trace,
                ]);
            }
        }
    }
}
