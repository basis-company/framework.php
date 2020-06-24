<?php

namespace Basis;

use Spiral\RoadRunner\Metrics;

class Metric
{
    use Toolkit;

    public const COUNTER = 'counter';
    public const GAUGE = 'gauge';
    public const HISTOGRAM = 'histogram';
    public const SUMMARY = 'summary';

    public string $type = self::GAUGE;
    public string $help;

    public array $labels = [];

    public function getNick(): string
    {
        $xtype = $this->get(Converter::class)->classToXtype(get_class($this));
        return str_replace('.', '_', substr($xtype, strlen('metric.')));
    }

    public function toArray(): array
    {
        $validTypes = [
            self::COUNTER,
            self::GAUGE,
            self::HISTOGRAM,
            self::SUMMARY,
        ];

        if (!in_array($this->type, $validTypes)) {
            throw new Exception("Invalid type: $this->type");
        }

        $array = [
            'help' => $this->help,
            'type' => $this->type,
        ];

        if ($this->labels !== null) {
            $array['labels'] = $this->labels;
        }

        return $array;
    }

    public function __call(string $name, array $arguments)
    {
        $value = 1;
        if (count($arguments)) {
            [ $value ] = $arguments;
        }

        $this->get(Metrics::class)
            ->$name($this->getNick(), $value, $this->labels);
    }
}
