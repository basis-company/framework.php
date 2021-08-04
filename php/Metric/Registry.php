<?php

namespace Basis\Metric;

use Basis\Metric;
use Basis\Metric\StartTime;
use Basis\Metric\Uptime;
use Basis\Registry as Meta;
use Basis\Toolkit;
use Tarantool\Client\Schema\Operations;

class Registry
{
    use Toolkit;

    private $hostname = '_hostname';

    public function getValue(Metric $metric, array $labels = []): float|int|null
    {
        $labels[$this->hostname] = gethostname();

        $metric = $this->findOne('metric', [
            'key' => $this->getKey($metric, $labels),
        ]);

        return $metric ? $metric->value : null;
    }

    public function setValue(Metric $metric, array $labels, $value): float|int|null
    {
        $labels[$this->hostname] = gethostname();

        $metric = $this->findOrCreate('metric', [
            'key' => $this->getKey($metric, $labels),
        ], [
            'key' => $this->getKey($metric, $labels),
            'class' => get_class($metric),
            'labels' => $labels,
            'value' => $value,
        ]);

        $metric->value = $value;
        $metric->save();

        return $metric->value;
    }

    public function increment(Metric $metric, array $labels, $amount)
    {
        $labels[$this->hostname] = gethostname();

        $instance = $this->findOrCreate('metric', [
            'key' => $this->getKey($metric, $labels),
        ], [
            'key' => $this->getKey($metric, $labels),
            'class' => get_class($metric),
            'labels' => $labels,
            'value' => 0,
        ]);

        $this->getMapper()->getClient()->getSpace('metric')
            ->update([$instance->key], Operations::add('value', $amount));

        $instance->getRepository()->forget($instance);

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

        foreach ($set as [ $class, $labels ]) {
            $metric = $this->get($class);
            $nick = $prefix . $metric->getNick();

            if (!array_key_exists($class, $typed)) {
                $typed[$class] = true;
                $output[] = sprintf('# HELP %s %s', $nick, $metric->getHelp());
                $output[] = sprintf('# TYPE %s %s', $nick, $metric->getType());
            } elseif ($output[count($output)-1] == "$nick 0") {
                array_pop($output);
            }
            $labels = get_object_vars(json_decode($labels));

            $value = $this->getValue($metric, $labels);
            unset($labels[$this->hostname]);
            if (count($labels)) {
                $nick .= json_encode($labels);
            }

            $output[] = sprintf("%s %s", $nick, $value);
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
            $start = $this->getValue($this->get(StartTime::class), [ $this->hostname => $hostname ] );
            $uptime = $this->getValue($this->get(Uptime::class), [ $this->hostname => $hostname ] );
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
