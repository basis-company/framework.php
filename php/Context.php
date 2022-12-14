<?php

namespace Basis;

use Carbon\Carbon;
use Throwable;

class Context
{
    public int $access;
    public int $channel = 0;

    /**
     * inter-service communication
     */
    public ?string $service = null;

    /**
     * client real ip
     */
    public ?string $ip = null;

    public int $company = 0;
    public int $person;
    public int $module = 0;

    /**
     * parent person
     */
    public int $parent = 0;

    /**
     * context event
     */
    public int $event = 0;

    public function execute($context, $callback)
    {
        $origin = $this->toArray();

        try {
            $this->reset($context);
            $result = call_user_func($callback);
            $this->reset($origin);
        } catch (Throwable $e) {
            $this->reset($origin);
            throw $e;
        }

        return $result;
    }

    public function reset($context = []): self
    {
        foreach ($this as $k => $_) {
            $this->$k = null;
        }
        $this->apply($context);

        return $this;
    }

    public function apply($data): self
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }

        return $this;
    }

    public function getPerson(): int
    {
        return $this->parent ?: $this->person;
    }

    public function toArray(): array
    {
        static $converter;
        if (!$converter) {
            $converter = new Converter();
        }

        return $converter->toArray($this);
    }
}
