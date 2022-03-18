<?php

namespace Basis\Job\Module;

use Basis\Nats\Client;

class Logs
{
    public function run(Client $client)
    {
        $subject = str_replace('-', '.', 'logs-' . gethostname());

        $client->subscribe($subject, function ($message) {
            echo $message, PHP_EOL;
        });

        while (true) {
            $client->process(PHP_INT_MAX);
        }
    }
}
