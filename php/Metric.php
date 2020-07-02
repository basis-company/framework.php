<?php

namespace Basis;

use Exception;
use Basis\Metric\Registry;

class Metric
{
    use Toolkit;

    public const COUNTER = 'counter';
    public const GAUGE = 'gauge';
    public const HISTOGRAM = 'histogram';

    public string $type = self::GAUGE;
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
            'help' => $this->help,
            'type' => $this->type,
        ];

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

    public function getRow()
    {
        return $this->getContainer()
            ->get(Registry::class)
            ->getRow($this);
    }

    public function getValue()
    {
        return $this->getRow()['value'];
    }

    public function setValue($value)
    {
        $this->getRow()['value'] = $value;
    }

    public function increment()
    {
        $this->getRow()['value'] = $this->getRow()['value'] + 1;
    }
}
