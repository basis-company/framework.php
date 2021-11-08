<?php

namespace Basis\Data;

use BadMethodCallException;

class Task
{
    public function __construct(
        private Queue $queue,
        private int $id,
        private string $state,
        private string $data
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function isReady(): bool
    {
        return 'r' === $this->state;
    }

    public function isTaken(): bool
    {
        return 't' === $this->state;
    }

    public function isDone(): bool
    {
        return '-' === $this->state;
    }

    public function isBuried(): bool
    {
        return '!' === $this->state;
    }

    public function isDelayed(): bool
    {
        return '~' === $this->state;
    }

    public function __call(string $method, array $arguments)
    {
        $valid = [
            'delete', 'release', 'touch', 'ack', 'bury', 'kick', 'peek'
        ];

        if (!in_array($method, $valid)) {
            throw new BadMethodCallException();
        }

        if (!$this->isTaken()) {
            throw new Exception("Task is not Taken");
        }

        $result = $this->getQueue()->getWrapper()->getClient()->call(
            $this->getQueue()->getTube() . ':' . $method,
            $this->id,
        );

        if ($result[0]) {
            $this->state = $result[0][1];
        }
    }
}
