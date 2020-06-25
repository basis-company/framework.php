<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Toolkit;
use Tarantool\Client\Client;
use Exception;

class Tarantool
{
    use Toolkit;

    public function init(Container $container)
    {
        $container->share(Client::class, function () {

            $options = [
                'uri' => getenv('TARANTOOL_CONNECTION'),
            ];

            if (!$options['uri']) {
                $address = $this->dispatch('resolve.address', [
                    'name' => $this->app->getName() . '-db',
                ]);
                $options['uri'] = 'tcp://' . $address->host . ':3301';
            }

            $mapping = [
                'connect_timeout' => 'TARANTOOL_CONNECT_TIMEOUT',
                'socket_timeout'  => 'TARANTOOL_SOCKET_TIMEOUT',
                'tcp_nodelay'     => 'TARANTOOL_TCP_NODELAY',
            ];

            foreach ($mapping as $key => $env) {
                if (getenv($env)) {
                    $options[$key] = getenv($env);
                }
            }

            $client = Client::fromOptions($options);

            try {
                $client->evaluate("box.session.su('admin')");
            } catch (Exception $e) {
            }

            return $client;
        });
    }
}
