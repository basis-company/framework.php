<?php

namespace Basis;

use Exception;

abstract class Job
{
    use Toolkit;

    protected function confirm($message)
    {
        $hash = md5($message);
        if (!is_array($this->_confirmations) || !in_array($hash, $this->_confirmations)) {
            throw new Exception(json_encode([
                'type' => 'confirm',
                'message' => $message,
                'hash' => $hash
            ]));
        }
    }
}
