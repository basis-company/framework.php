<?php

namespace Basis;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        $row = $message;

        if (is_array($row) || is_object($row)) {
            if (count($context)) {
                $row['context'] = $context;
            }
            $row = json_encode($row);
        } elseif (count($context)) {
            $row .= ' ' . json_encode($context);
        }

        if (constant('STDOUT')) {
            fwrite(STDOUT, $row . PHP_EOL);
        } else {
            echo $row, PHP_EOL;
        }
    }
}
