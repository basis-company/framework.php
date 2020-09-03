<?php

namespace Basis\Metric;

use Basis\Metric;
use Swoole\Table;

class Registry
{
    protected $table;

    public function __construct()
    {
        $this->table = new Table(32);
        $this->table->column('type', Table::TYPE_STRING, 16);
        $this->table->column('nick', Table::TYPE_STRING, 64);
        $this->table->column('help', Table::TYPE_STRING, 256);
        $this->table->column('value', Table::TYPE_FLOAT);
        $this->table->create();
    }

    public function getRow(Metric $metric)
    {
        if (!$this->table->offsetExists($metric->getNick())) {
            $this->table[$metric->getNick()] = [
                'help' => $metric->getHelp(),
                'nick' => $metric->getNick(),
                'type' => $metric->getType(),
            ];
        }

        return $this->table[$metric->getNick()];
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
        foreach ($keys as $key) {
            $v = $this->table[$key];
            $nick = $prefix . $v['nick'];
            if ($v['help']) {
                $output[] = sprintf('# HELP %s %s', $nick, $v['help']);
            }
            $output[] = sprintf('# TYPE %s %s', $nick, $v['type']);
            $output[] = sprintf("%s %s", $nick, $v['value']);
        }
        
        return implode(PHP_EOL, $output);
    }
}
