<?php

namespace Basis;

use Basis\Feedback\Choose;
use Basis\Feedback\Confirm;
use Basis\Feedback\Info;
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

    protected function choose(string $message, array $options): string
    {
        return (new Choose($message))
            ->setOptions($options)
            ->process($this);
    }

    protected function confirm(string $message): bool
    {
        return !!(new Confirm($message))
            ->process($this);
    }
}
