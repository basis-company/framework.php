<?php

namespace Basis\Configuration;

use Basis\Container;
use Basis\Toolkit;
use GuzzleHttp\Client;
use Psr\Http\Message\ServerRequestInterface;

class Guzzle
{
    use Toolkit;

    public function init(Container $container)
    {
        $container->share(Client::class, function () {
            $headers = [
                'transfer-encoding' => 'chunked',
            ];
            $container = $this->getContainer();
            if ($container->has(ServerRequestInterface::class)) {
                $request = $container->get(ServerRequestInterface::class);
                foreach ([ 'x-session', 'x-real-ip' ] as $header) {
                    if ($request->hasHeader($header)) {
                        $headers[$header] = $request->getHeaderLine($header);
                    }
                }
            }
            return new Client([
                'headers' => $headers
            ]);
        });
    }
}
