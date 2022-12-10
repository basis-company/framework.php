<?php

namespace Basis\Controller;

use Basis\Context;
use Basis\Dispatcher;
use DateTimeInterface;
use Basis\Toolkit;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Rest
{
    use Toolkit;

    public function process(ServerRequestInterface $request)
    {
        $dispatcher = $this->get(Dispatcher::class);
        $job = strtolower(str_replace('/', '.', substr($request->getUri()->getPath(), 1)));

        if (!$dispatcher->getClass($job)) {
            return;
        }

        $cookies = $request->getCookieParams();
        if (!array_key_exists('access', $cookies)) {
            return new Response(401);
        }

        $token = $cookies['access'];
        $key = null;
        if (file_exists('jwt_key')) {
            $key = file_get_contents('jwt_key');
        } else {
            $key = file_get_contents('http://guard/guard/key');
            file_put_contents('jwt_key', $key);
        }

        if (!$key) {
            return new Response(500);
        }

        $key = new Key(file_get_contents('jwt_key'), 'RS256');
        $payload = JWT::decode($cookies['access'], $key);

        $context = $this->get(Context::class);
        $context->access = $payload->access;
        $context->channel = (int) $request->getHeaderLine('x-channel');
        $context->company = $payload->company;
        $context->person = $payload->person;
        $context->module = $payload->module;

        if ($request->getHeaderLine('x-real-ip')) {
            $context->apply([
                'ip' => $request->getHeaderLine('x-real-ip'),
            ]);
        }

        $params = match ($request->getMethod()) {
            'GET' => $request->getQueryParams(),
            'POST' => $request->getParsedBody(),
        };

        if ($params == null) {
            $params = json_decode($request->getBody(), true);
        }

        $result = $dispatcher->dispatch($job, $params ?: []);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (property_exists($result, 'expire')) {
            $headers['Expires'] = $this->getDate($result->expire)
                ->format(DateTimeInterface::RFC7231);
        }

        return new Response(200, $headers, json_encode($result));
    }
}
