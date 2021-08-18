<?php

namespace Basis\Test;

use Basis\Application as Basis;
use Basis\Registry;
use Basis\Test;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Application extends Basis
{
    public function __construct(Test $test)
    {
        parent::__construct();

        $container = $this->getContainer();
        $container->share(Test::class, $test);

        $logger = new Logger('testing');
        $logger->pushHandler(new NullHandler());

        $container->share(LoggerInterface::class, $logger);

        $classes = $this->get(Registry::class)->listClasses('Test');
        foreach ($classes as $class) {
            if (strpos($class, 'Basis\\Test') === false) {
                continue;
            }
            $origin = str_replace('Test\\', '', $class);
            if (!class_exists($origin)) {
                continue;
            }
            $container->share($origin, $class);
        }
    }
}
