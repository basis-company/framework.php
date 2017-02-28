<?php

namespace Basis;

use Iterator;

class Fiber
{
    private $tick = 0;
    private $iterators = [];
    private $threads = [];
    private $running = false;

    public function isRunning()
    {
        return $this->running;
    }

    public function attach($callback)
    {
        $this->threads[] = $callback;
    }

    public function run()
    {
        if(!$this->isRunning()) {
            foreach($this->process() as $tick) {}
        }
    }

    public function process() {
        $this->running = true;
        while(true) {
            if(!count($this->threads)) {
                yield $this->tick++;
            }
            foreach($this->threads as $index => $callback) {
                yield $this->tick++;
                if(!array_key_exists($index, $this->iterators)) {
                    $this->iterators[$index] = call_user_func($callback);
                    if($this->iterators[$index] instanceof Iterator) {
                        $this->iterators[$index]->current();
                        continue;
                    }

                } else {
                    if($this->iterators[$index]->valid()) {
                        $this->iterators[$index]->next();
                        continue;
                    }
                }

                unset($this->iterators[$index]);
                unset($this->threads[$index]);

                if(!count($this->threads)) {
                    $this->running = false;
                    return;
                }
            }
        }
    }
}

