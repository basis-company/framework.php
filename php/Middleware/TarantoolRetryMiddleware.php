<?php

namespace Basis\Middleware;

use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

class TarantoolRetryMiddleware implements Middleware
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * delay in milliseconds
     * @var integer
     */
    public int $interval = 500;

    /**
     * delay in milliseconds
     * @var integer
     */
    public int $maxRetries = 60;

    public function process(Request $request, Handler $handler): Response
    {
        $retries = 0;

        while (true) {
            try {
                return $handler->handle($request);
            } catch (ConnectionFailed $e) {
            } catch (CommunicationFailed | UnexpectedResponse $e) {
                $handler->getConnection()->close();
            } catch (RequestFailed $e) {
                if (strpos($e->getMessage(), 'call box.cfg{} first') === false) {
                    break;
                }
            }
            if ($retries++ >= $this->maxRetries) {
                break;
            }

            $sleep = ($this->interval * $retries) / 1000;

            $this->logger->info([
                'exception' => array_reverse(explode('\\', get_class($e)))[0],
                'message' => $e->getMessage(),
                'sleep' => round($sleep, 3),
            ]);

            if (class_exists(Coroutine::class) && Coroutine::getContext() !== null) {
                Coroutine::sleep($sleep);
            } else {
                usleep($sleep * 1000000);
            }
        }

        throw $e;
    }
}
