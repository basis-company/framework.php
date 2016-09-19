<?php

namespace Example\Providers;

use Example\BusinessLogic;
use League\Container\ServiceProvider\AbstractServiceProvider;

class BusinessLogicProvider extends AbstractServiceProvider
{
    protected $provides = [
        BusinessLogic::class
    ];

    public function register()
    {
    }
}
