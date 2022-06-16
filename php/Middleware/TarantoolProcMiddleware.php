<?php

namespace Basis\Middleware;

use Basis\Dispatcher;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

class TarantoolProcMiddleware implements Middleware
{
    private const ER_NO_SUCH_PROC = 33;

    public function __construct(public $service, private Dispatcher $dispatcher)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (RequestFailed $e) {
            if ($e->getCode() != self::ER_NO_SUCH_PROC) {
                throw $e;
            }
            $this->dispatcher->dispatch('tarantool.migrate', [], $this->service);
            sleep(3);
            return $handler->handle($request);
        }
    }
}
