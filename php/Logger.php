<?php

namespace Basis;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Throwable;

class Logger extends AbstractLogger
{
    public function exception(Throwable $e, string $level = LogLevel::INFO)
    {
        $data = [
            'type' => 'exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        $this->log($level, $data);
    }

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

        if (defined('STDOUT')) {
            fwrite(STDOUT, $row . PHP_EOL);
        } else {
            echo $row, PHP_EOL;
        }
    }
}
