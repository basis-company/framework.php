<?php

use Basis\Config;
use Basis\Logger;
use Fluent\Logger\FluentLogger;
use League\Container\Container;

class LoggerTest extends TestSuite
{
    function test()
    {
        $mock = $this->getMockBuilder(FluentLogger::class)
            ->setMethods(['post'])
            ->disableOriginalConstructor()
            ->getMock();

        $logger = new Logger($mock, 'example');
        $this->assertSame($mock, $logger->getLogger());

        $mock->expects($this->once())
            ->method('post')
            ->with('example', ['key' => 'value'])
            ->will($this->returnValue('OK'));

        $this->assertSame(
            $logger->log(['key' => 'value']),
            'OK'
        );
    }
}