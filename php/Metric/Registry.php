<?php

namespace Basis\Metric;

use Basis\Metric;
use Swoole\Table;

class Registry
{
    protected $table;

    public function __construct()
    {
        $this->table = new Table(512);
        $this->table->column('type', Table::TYPE_STRING, 16);
        $this->table->column('nick', Table::TYPE_STRING, 64);
        $this->table->column('labels', Table::TYPE_STRING, 128);
        $this->table->column('help', Table::TYPE_STRING, 128);
        $this->table->column('value', Table::TYPE_FLOAT);
        $this->table->create();
    }

    public function getRow(Metric $metric, $labels = [])
    {
        $rendered = [];
        foreach ($labels as $k => $v) {
            $rendered[] = $k . '="' . $v . '"';
        }
        $labels = implode(',', $rendered);
        $key = $metric->getNick() . ' ' . $labels;

        if (!$this->table->offsetExists($key)) {
            $this->table[$key] = [
                'help' => $metric->getHelp(),
                'nick' => $metric->getNick(),
                'type' => $metric->getType(),
                'labels' => $labels,
            ];
        }

        return $this->table[$key];
    }

    public function render(string $prefix = ''): string
    {
        // sorted key collection
        $keys = [];
        foreach ($this->table as $k => $v) {
            $keys[] = $k;
        }
        sort($keys);

        // render prometheus format
        $output = [];
        $typed = [];
        foreach ($keys as $key) {
            $v = $this->table[$key];
            $nick = $prefix . $v['nick'];
            if (!array_key_exists($nick, $typed)) {
                $typed[$nick] = true;
                if ($v['help']) {
                    $output[] = sprintf('# HELP %s %s', $nick, $v['help']);
                }
                $output[] = sprintf('# TYPE %s %s', $nick, $v['type']);
            } elseif ($output[count($output) - 1] == "$nick 0") {
                // metric with labels should not have zero row
                array_pop($output);
            }
            $labels = [];
            if ($v['labels']) {
                $nick .= '{' . $v['labels'] . '}';
            }
            $output[] = sprintf("%s %s", $nick, $v['value']);
        }

        return implode(PHP_EOL, $output);
    }
}
