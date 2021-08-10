<?php

namespace Basis\Job\Module;

use Basis\Metric\BackgroundStart;
use Basis\Toolkit;
use Tarantool\Mapper\Plugin\Spy;
use Throwable;

class Background
{
    use Toolkit;

    public function run()
    {
        if (!class_exists(\Job\Background::class)) {
            return [
                'msg' => 'no background job',
            ];
        }

        $this->get(BackgroundStart::class)->update();
        $this->getMapper()->getPlugin(Spy::class)->reset();

        $this->dispatch('module.process', [
            'job' => 'background'
        ]);
    }
}
