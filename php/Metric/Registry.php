<?php

namespace Basis\Metric;

use Basis\Metric;
use Basis\Metric\StartTime;
use Basis\Metric\Uptime;
use Basis\Registry as Meta;
use Basis\Toolkit;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;

class Registry
{
    use Toolkit;

    private $hostname = '_hostname';

    public function getValue(Metric $metric, array $labels = []): float | int | null
    {
        $labels[$this->hostname] = gethostname();
        $key = $this->getKey($metric, $labels);
        $class = get_class($metric);

        $result = $this->getMapper()->getClient()->getSpace('metric')
            ->select(Criteria::index('key')->andKey([$key]));

        if (count($result)) {
            return $result[0][3];
        }

        return null;
    }

    public function setValue(Metric $metric, array $labels, $value): float | int | null
    {
        $labels[$this->hostname] = gethostname();
        $key = $this->getKey($metric, $labels);
        $class = get_class($metric);

        $this->getMapper()->getClient()->getSpace('metric')
            ->upsert([$key, $class, $labels, $value], Operations::set(3, $value));

        return $value;
    }

    public function increment(Metric $metric, array $labels, $amount)
    {
        $labels[$this->hostname] = gethostname();
        $key = $this->getKey($metric, $labels);
        $class = get_class($metric);

        $this->getMapper()->getClient()->getSpace('metric')
            ->upsert([$key, $class, $labels, 0], Operations::add(3, $amount));

        return $this->getValue($metric, $labels);
    }

    protected function getKey(Metric $metric, array $labels)
    {
        ksort($labels);

        return get_class($metric) . ':' . json_encode($labels);
    }

    protected function getMetrics(): array
    {
        $metrics = [];

        foreach ($this->find('metric') as $metric) {
            if ($metric->labels[$this->hostname] !== gethostname()) {
                continue;
            }
            $metrics[$metric->key] = $metric->value;
        }

        return $metrics;
    }

    public function render(string $prefix = ''): string
    {
        $set = [];
        $values = [];
        foreach ($this->getMetrics() as $key => $value) {
            $set[$key] = explode(":", $key, 2);
            $values[$key] = $value;
        }

        ksort($set);

        $output = [];
        $typed = [];

        foreach ($set as $key => [ $class, $labels ]) {
            $metric = $this->get($class);
            $nick = $prefix . $metric->getNick();

            if (!array_key_exists($class, $typed)) {
                $typed[$class] = true;
                $output[] = sprintf('# HELP %s %s', $nick, $metric->getHelp());
                $output[] = sprintf('# TYPE %s %s', $nick, $metric->getType());
            } elseif ($output[count($output) - 1] == "$nick 0") {
                array_pop($output);
            }
            $labels = json_decode($labels);
            if (is_object($labels)) {
                $labels = get_object_vars($labels);
            }

            unset($labels[$this->hostname]);
            if (count($labels)) {
                $kv = [];
                foreach ($labels as $k => $v) {
                    $kv[] = $k . '="' . $v . '"';
                }
                $nick .= '{' . implode(',', $kv) . '}';
            }

            $output[] = sprintf("%s %s", $nick, $values[$key]);
        }

        return implode(PHP_EOL, $output);
    }

    public function housekeeping()
    {
        $todo = [];
        foreach ($this->find('metric') as $metric) {
            if (!is_array($metric->labels)) {
                $this->getMapper()->remove($metric);
                continue;
            }
            if (!array_key_exists($this->hostname, $metric->labels)) {
                $this->getMapper()->remove($metric);
                continue;
            }

            $hostname = $metric->labels[$this->hostname];
            if ($hostname == gethostname()) {
                continue;
            }
            $todo[] = $hostname;
        }

        foreach ($todo as $hostname) {
            $start = $this->getValue($this->get(StartTime::class), [ $this->hostname => $hostname ]);
            $uptime = $this->getValue($this->get(Uptime::class), [ $this->hostname => $hostname ]);
            $silent = time() - $start + $uptime;
            if ($silent < 60) {
                continue;
            }

            $this->info('metric cleanup', [
                'hostname' => $hostname,
                'contact_ago' => intval($silent),
            ]);
            foreach ($this->find('metric') as $metric) {
                if ($metric->labels[$this->hostname] == $hostname) {
                    $this->getMapper()->remove($metric);
                }
            }
        }
    }
}
