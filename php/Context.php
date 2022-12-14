<?php

namespace Basis;

use Carbon\Carbon;
use Throwable;

class Context
{
    public int $access;
    public ?int $channel = null;

    /**
     * inter-service communication
     */
    public ?string $service = null;

    /**
     * client real ip
     */
    public ?string $ip = null;

    public ?int $company = null;
    public int $person;
    public ?int $module = null;

    /**
     * parent person
     */
    public ?int $parent = null;

    /**
     * context event
     */
    public ?int $event = null;

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
