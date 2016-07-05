<?php

use Basis\Application;

class TestSuite extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $this->app = new Application(__DIR__.DIRECTORY_SEPARATOR.'example');
    }
}