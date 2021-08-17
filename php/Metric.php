<?php

namespace Basis;

use Exception;
use Basis\Metric\Registry;
use Basis\Telemetry\Metrics\Operations;

class Metric
{
    use Toolkit;

    public const COUNTER = 'counter';
    public const GAUGE = 'gauge';
    public const HISTOGRAM = 'histogram';

    public string $type = self::COUNTER;
    public string $help;

    public array $labels = [];

    public function getNick(): string
    {
        static $nick;

        if ($nick === null) {
            $converter = $this->get(Converter::class);
            $xtype = $converter->toUnderscore(get_class($this));
            $position = strpos($xtype, 'metric_');
            $length = strlen('metric_');
            $nick = substr($xtype, $position + $length);
        }

        return $nick;
    }

    public function toArray(): array
    {
        $validTypes = [
            self::COUNTER,
            self::GAUGE,
            self::HISTOGRAM,
        ];

        if (!in_array($this->type, $validTypes)) {
            throw new Exception("Invalid type: $this->type");
        }

        $array = [
            'type' => $this->type,
        ];

        if ($this->help !== null) {
            $array['help'] = $this->help;
        }

        if ($this->labels !== null) {
            $array['labels'] = $this->labels;
        }

        return $array;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getHelp()
    {
        return $this->help;
    }

    public function getValue($labels = [])
    {
        return $this->get(Registry::class)->getValue($this, $labels);
    }

    public function setValue($value, $labels = [])
    {
        $this->get(Operations::class)->set($this->getNick(), $value, $labels);
    }

    public function increment($amount = 1, $labels = [])
    {
        $this->get(Operations::class)->increment($this->getNick(), $amount, $labels);
    }
}
