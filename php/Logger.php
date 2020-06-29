<?php

namespace Basis;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        $row = $message;

        if (count($context)) {
            $row .= ' ' . json_encode($context);
        }

        $row .= PHP_EOL;

        echo $row;
    }
}
