<?php

namespace Test;

use Basis\Application;
use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase
{
    protected $app;

    public function setup()
    {
        $location = dirname(dirname(__DIR__));
        chdir($location);
        $this->app = new Application($location);
    }
}
