<?php

namespace Basis;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response as AmpResponse;
use Amp\Http\Server\Router;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Http\Status;
use Exception;
use LogicException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Http
{
    use Toolkit;

    private ?Router $router = null;
    private ?array $mapping = null;
    private bool $logging = true;

    public function setLogging(bool $logging): self
    {
        $this->logging = $logging;
        return $this;
    }

    public function getRouter(): Router
    {
        if ($this->router === null) {
            $this->router = new Router();
            $mapping = $this->getMapping();
            foreach ($mapping as $pattern => $target) {
                [ $class, $method ] = $target;
                $handler = new CallableRequestHandler(function (AmpRequest $request) use ($pattern, $class, $method) {
                    return $this->handle($request, $pattern, $class, $method);
                });
                $this->router->addRoute('GET', $pattern, $handler);
                $this->router->addRoute('POST', $pattern, $handler);
            }
            $assets = new DocumentRoot(getcwd());
            $assets->setFallback(new CallableRequestHandler(function (AmpRequest $request) use ($mapping) {
                if (strpos($request->getUri()->getPath(), '/api/index/') === 0) {
                    [ $class, $method ] = $mapping['/api'];
                    return $this->handle($request, '/api/index', $class, $method);
                }
                return new AmpResponse(
                    Status::NOT_FOUND,
                    [ 'Content-Type' => 'text/plain; charset=utf-8' ],
                    'Not found: ' . $request->getUri()->getPath()
                );
            }));

            $this->router->setFallback($assets);
        }

        return $this->router;
    }

    private function handle(AmpRequest $request, string $pattern, string $class, string $method)
    {
        $start = microtime(true);

        $uri = $request->getUri()->getPath();
        $params = [];

        if ($request->getUri()->getQuery() !== '') {
            parse_str($request->getUri()->getQuery(), $params);
            $uri = $uri . '?' . $request->getUri()->getQuery();
        }

        $serverRequest = new ServerRequest(
            (string) $request->getMethod(),
            (string) $uri,
            (array) $request->getHeaders(),
            (string) '',
            (string) $request->getProtocolVersion(),
            (array) []
        );

        if (count($params)) {
            $serverRequest = $serverRequest->withQueryParams($params);
        }

        if ($request->getMethod() == 'POST') {
            $form = yield FormParser\parseForm($request);
            $parsedBody = $form->getValues();
            foreach ($parsedBody as $k => $v) {
                if (is_array($v) && count($v) == 1) {
                    $parsedBody[$k] = $v[0];
                }
            }
            $serverRequest = $serverRequest->withParsedBody($parsedBody);
        }

        $url = trim(substr($request->getUri()->getPath(), strlen($pattern)), '/');
        $arguments = [
            'uri' => $request->getUri()->getPath(), // absolute path
            'url' => $url, // relative path
            'chain' => $url ? explode('/', $url) : [], // relative chain
        ];

        ob_start();
        try {
            $thread = $this->app->fork();
            $thread->getContainer()->share(ServerRequestInterface::class, $serverRequest);
            $response = $thread->getContainer()->call($class, $method, $arguments);
        } catch (Throwable $e) {
            $response = $class . '::' . $method . '<br/>' . $e->getMessage();
        }
        $output = ob_get_clean();

        if (!$response instanceof ResponseInterface) {
            $headers = [
                'Content-Type' => 'text/plain; charset=utf-8',
            ];
            if (is_array($response) || is_object($response)) {
                $headers['Content-Type'] = 'application/json; charset=utf-8';
                $response = json_encode($response);
            }

            if ($output) {
                $headers['Content-Type'] = 'text/plain; charset=utf-8';
                $response = $output . $response;
            }

            $response = new Response(200, $headers, $response);
        }

        $log = [
            'method' => $request->getMethod(),
            'url' => substr($arguments['uri'], 1),
        ];

        $time = microtime(true) - $start;
        if ($time >= 0.001) {
            $log['time'] = round($time, 3);
        }

        if ($this->logging) {
            $this->get(LoggerInterface::class)->info($log);
        }

        return new AmpResponse($response->getStatusCode(), $response->getHeaders(), $response->getBody());
    }

    public function getMapping(): array
    {
        if ($this->mapping === null) {
            $this->mapping = [];
            $converter = $this->get(Converter::class);
            $registry = $this->get(Registry::class);
            $toolkit = $registry->getPublicMethods(Toolkit::class);
            $dynamic = [];
            foreach ($registry->listClasses('controller') as $class) {
                $namespace = null;
                foreach ($registry->getPublicMethods($class) as $name) {
                    if (in_array($name, ['__construct', '__debugInfo'])) {
                        continue;
                    }
                    if (in_array($name, $toolkit)) {
                        continue;
                    }
                    if (!$namespace) {
                        $start = strpos($class, 'Controller\\') + 11;
                        $namespace = $converter->classToXtype(substr($class, $start));
                    }
                    $route = "/" . $namespace;
                    if ($name == '__process') {
                        $route .= "/{query}";
                        $dynamic[$route] = [ $class, $name ];
                        continue;
                    } elseif ($name !== 'index') {
                        $route .= "/$name";
                    }
                    $start = strlen($route);
                    $this->mapping[$route] = [ $class, $name ];
                }
                // dynamic routes should be latest
                foreach ($dynamic as $route => $info) {
                    $this->mapping[$route] = $info;
                }
            }
        }

        return $this->mapping;
    }

    public function getRoutes(): array
    {
        return array_keys($this->getMapping());
    }
}
