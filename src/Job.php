<?php

namespace Basis;

use Exception;

abstract class Job
{
    use Toolkit;

    protected function require($name)
    {
        if (!$this->$name) {
            throw new Exception("$name is not defined");
        }
    }

    protected function confirm($message)
    {
        $hash = md5($message);
        if (!property_exists($this, '_confirmations') || !is_array($this->_confirmations) || !in_array($hash, $this->_confirmations)) {
            throw new Exception(json_encode([
                'type' => 'confirm',
                'message' => $message,
                'hash' => $hash
            ]));
        }
    }
}
