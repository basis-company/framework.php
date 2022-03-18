<?php

namespace Basis;

use LogicException;
use Symfony\Component\Process\Process;

class Task
{
    public readonly string $job;
    public array $params;

    public int|Closure $delay = 1;
    public int $limit = PHP_INT_MAX;
    public int $timeout = PHP_INT_MAX;

    private ?Process $process = null;
    private array $command = [];
    private float $startedAt = 0;
    private float $stoppedAt = 0;

    public function __call($method, $arguments): self
    {
        if (!count($arguments)) {
            throw new LogicException("Invalid method call");
        }

        $this->$method = $arguments[0];

        return $this;
    }

    public function start()
    {
        if ($this->process) {
            return;
        }

        $delay = is_callable($this->delay) ? $this->delay() : $this->delay;
        if (!$this->limit || ($this->stoppedAt + $delay) > microtime(true)) {
            return;
        }

        if (!count($this->command)) {
            $arguments = [];
            foreach ($this->params as $k => $v) {
                $arguments[] = "$k=$v";
            }
            $this->command = ['php', 'console', $this->job, ...$arguments];
        }

        $this->process = new Process($this->command, '/var/www/html');
        $this->process->setTimeout($this->timeout);
        $this->process->start();

        $this->limit--;
        $this->startedAt = microtime(true);
    }

    public function stop()
    {
        if (!$this->process) {
            return;
        }

        $this->process->stop();
    }

    public function finalize()
    {
        if (!$this->process) {
            return;
        }

        $pid = $this->process->getPid();
        $complete = !$pid;

        if (!$complete) {
            $stat = @file_get_contents("/proc/$pid/stat");
            sscanf($stat, "%d %s %c", $rpid, $cmd, $status);
            if ($status == 'Z' || !$status) {
                if ($status == 'Z') {
                    $this->process->wait();
                }
                $complete = true;
            }
        }

        if ($complete) {
            $this->process = null;
            $this->stoppedAt = microtime(true);
        }
    }
}
