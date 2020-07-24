<?php

namespace Basis\Metric;

use Basis\Metric;
use Swoole\Table;

class Registry extends Table
{
    public function __construct()
    {
        parent::__construct(32);
        $this->column('type', Table::TYPE_STRING, 16);
        $this->column('nick', Table::TYPE_STRING, 64);
        $this->column('help', Table::TYPE_STRING, 256);
        $this->column('value', Table::TYPE_FLOAT);
        $this->create();
    }

    public function getRow(Metric $metric)
    {
        if (!$this->offsetExists($metric->getNick())) {
            $this[$metric->getNick()] = [
                'help' => $metric->getHelp(),
                'nick' => $metric->getNick(),
                'type' => $metric->getType(),
            ];
        }

        return $this[$metric->getNick()];
    }

    public function render(string $prefix = ''): string
    {
        // sorted key collection
        $keys = [];
        foreach ($this as $k => $v) {
            $keys[] = $k;
        }
        sort($keys);

        // render prometheus format
        foreach ($keys as $key) {
            $v = $this[$key];
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
