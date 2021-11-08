<?php

namespace Basis\Data;

class Queue
{
    public function __construct(private Wrapper $wrapper, private string $name)
    {
    }

    public function getWrapper(): Wrapper
    {
        return $this->wrapper;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTube(): string
    {
        return 'queue.tube.' . $this->getName();
    }

    public function put($data): Task
    {
        $result = $this->getWrapper()->getClient()->call($this->getTube() . ':put', $data);

        return new Task($this, ...$result[0]);
    }

    public function take(): ?Task
    {
        $result = $this->getWrapper()->getClient()->call($this->getTube() . ':take', 0.001);

        if (count($result) && $result[0] !== null) {
            return new Task($this, ...$result[0]);
        }

        return null;
    }
}
