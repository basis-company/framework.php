<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Context;
use Basis\Dispatcher;
use Basis\Telemetry\Tracing\SpanContext;
use Basis\Telemetry\Tracing\Tracer;
use Basis\Toolkit;
use DateTimeInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $token = null;

        $authorization = $request->getHeaderLine('authorization');
        if ($authorization && strpos($authorization, 'Bearer ') === 0) {
            $token = (explode(' ', $authorization)[1]);
        }

        if (!$token) {
            return new Response(401);
        }

        $payload = $this->get(Application::class)->getTokenPayload($token);

        $context = $this->get(Context::class);
        $context->channel = (int) $request->getHeaderLine('x-channel') ?: 0;

        foreach ($payload as $k => $v) {
            if (property_exists($context, $k)) {
                $context->$k = $v;
            }
        }

        if ($request->getHeaderLine('x-real-ip')) {
            $context->apply([
                'ip' => $request->getHeaderLine('x-real-ip'),
            ]);
        }

        if ($request->getHeaderLine('x-trace-id')) {
            $traceId = $request->getHeaderLine('x-trace-id');
            $spanId = $request->getHeaderLine('x-span-id');

            $parent = SpanContext::restore($traceId, $spanId);
            $span = SpanContext::restore($traceId, SpanContext::generate()->getSpanId());

            $tracer = new Tracer($span);
            $tracer->getActiveSpan()->setParentSpanContext($parent);

            $this->getContainer()->share(Tracer::class, $tracer);
        }

        $params = match ($request->getMethod()) {
            'GET' => $request->getQueryParams(),
            'POST' => json_decode($request->getBody(), true),
        };

        if ($params == null) {
            $params = $request->getParsedBody();
        }

        $result = $dispatcher->dispatch($job, $params ?: []);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

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
