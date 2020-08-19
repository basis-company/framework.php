<?php

namespace Basis;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        $row = $message;

        if (is_array($row) || is_object($row)) {
            $row = json_encode($row);
        }

        if (count($context)) {
            $row .= ' ' . json_encode($context);
        }

        $row .= PHP_EOL;

        echo $row;
    }
}
