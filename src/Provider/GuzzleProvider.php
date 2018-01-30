<?php

namespace Basis\Provider;

use GuzzleHttp\Client;
use League\Container\ServiceProvider\AbstractServiceProvider;

class GuzzleProvider extends AbstractServiceProvider
{
    protected $provides = [
        Client::class,
    ];

    public function register()
    {
        $this->getContainer()->share(Client::class, function () {
            return new Client([
                'headers' => [
                    'transfer-encoding' => 'chunked',
                    'x-real-ip' => $_SERVER['HTTP_X_REAL_IP'],
                    'x-session' => $_SERVER['HTTP_X_SESSION'],
                ]
            ]);
        });
    }
}
