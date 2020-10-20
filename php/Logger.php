<?php

namespace Basis;

use Amp\ByteStream\OutputStream;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public OutputStream $stream;

    public function __construct(OutputStream $stream)
    {
        $this->stream = $stream;
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

        $this->stream->write($row . PHP_EOL);
    }
}
