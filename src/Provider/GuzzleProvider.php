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
            $headers = [
                'transfer-encoding' => 'chunked',
            ];
            if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
                $headers['x-real-ip'] = $_SERVER['HTTP_X_REAL_IP'];
            }
            if (array_key_exists('HTTP_X_SESSION', $_SERVER)) {
                $headers['x-session'] = $_SERVER['HTTP_X_SESSION'];
            }
            return new Client([
                'headers' => $headers
            ]);
        });
    }
}
