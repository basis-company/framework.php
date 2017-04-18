<?php

namespace Providers;

use BusinessLogic;
use League\Container\ServiceProvider\AbstractServiceProvider;

class BusinessLogicProvider extends AbstractServiceProvider
{
    protected $provides = [
        BusinessLogic::class
    ];

    public function register()
    {
        $this->container->share(BusinessLogic::class, function() {
            return new BusinessLogic();
        });
    }
}
