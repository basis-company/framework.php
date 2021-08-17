<?php

namespace Basis\Middleware;

use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Tarantool\Client\Connection\StreamConnection;
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
    public function __construct(protected LoggerInterface $logger)
    {
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
                // sleep and reconnect
            } catch (CommunicationFailed | UnexpectedResponse $e) {
                // retry without any delay
                $handler->getConnection()->close();
                continue;
            } catch (RequestFailed $e) {
                $retry = false;
                $allowed = [
                    'instance is in read-only mode',
                    'call box.cfg{} first',
                ];
                foreach ($allowed as $candidate) {
                    if (strpos($e->getMessage(), $candidate) !== false) {
                        $retry = true;
                        break;
                    }
                }
                if (!$retry) {
                    break;
                }
            }
            if ($retries++ >= $this->maxRetries) {
                break;
            }

            $sleep = ($this->interval * $retries) / 1000;

            $data = [
                'exception' => array_reverse(explode('\\', get_class($e)))[0],
                'message' => $e->getMessage(),
                'sleep' => round($sleep, 3),
            ];

            static $property;
            if ($property === null) {
                $property = new ReflectionProperty(StreamConnection::class, 'uri');
                $property->setAccessible(true);
            }
            $uri = $property->getValue($handler->getConnection());
            if (strpos($data['message'], $uri) === false) {
                $data['uri'] = $uri;
            }

            $this->logger->alert('connection failure', $data);

            usleep($sleep * 1000000);
        }

        throw $e;
    }
}
