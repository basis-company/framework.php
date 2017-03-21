<?php

namespace Basis;

use Fluent\Logger\FluentLogger;

class Logger
{
    private $logger;
    private $tag;

    public function __construct(FluentLogger $logger, $tag)
    {
        $this->logger = $logger;
        $this->tag = $tag;
    }

    public function log(array $data)
    {
        return $this->logger->post($this->tag, $data);
    }

    public function getLogger()
    {
        return $this->logger;
    }
}