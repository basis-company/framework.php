<?php

namespace Basis;

use Fluent\Logger\FluentLogger;

class Logger
{
    private $logger;
    private $tag;

    function __construct(FluentLogger $logger, $tag)
    {
        $this->logger = $logger;
        $this->tag = $tag;
    }

    function log(array $data)
    {
        return $this->logger->post($this->tag, $data);
    }
}