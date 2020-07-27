<?php

namespace Basis;

use Exception;
use LogicException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Throwable;

class Http
{
    use Toolkit;

    private ?array $mapping = null;

    public function getMapping(): array
    {
        if ($this->mapping === null) {
            $this->mapping = [];
            $converter = $this->get(Converter::class);
            $registry = $this->get(Registry::class);
            $toolkit = $registry->getPublicMethods(Toolkit::class);

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
                    if ($name == '__process') {
                        $route = "$namespace/*";
                        $start = strlen($route);
                    } else {
                        $route = "$namespace/$name";
                        $start = strlen($route) + 1;
                    }
                    $this->mapping[$route] = [ $class, $name, $start ];
                    if (strpos($route, '*') === false) {
                        $this->mapping[$route . '/*'] = [ $class, $name, $start + 1 ];
                    }
                }
            }
        }

        return $this->mapping;
    }

    public function getRoutes(): array
    {
        return array_keys($this->getMapping());
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->getContainer();
        $container->share(ServerRequestInterface::class, $request);

        $uri = $request->getUri()->getPath();
        $chain = $this->getChain($uri);
        $path = implode('/', $chain);

        $pattern = $path;
        $mapping = $this->getMapping();
        if (!array_key_exists($pattern, $mapping)) {
            foreach ($mapping as $candidate => $callback) {
                if ($this->match($path, $candidate)) {
                    $pattern = $candidate;
                    break;
                }
            }
        }

        ob_start();

        if (array_key_exists($pattern, $mapping)) {
            [ $class, $method, $start ] = $mapping[$pattern];

            $url = trim(substr($uri, $start), '/');
            $arguments = [
                // absolute path
                'uri' => $uri,
                // relative path
                'url' => $url,
                // relative chain
                'chain' => $url ? explode('/', $url) : [],
            ];
            try {
                $result = $container->call($class, $method, $arguments);
            } catch (Throwable $e) {
                $result = $class . '::' . $method . '<br/>' . $e->getMessage();
            }
        } else {
            $result = "Page not found: $uri";
        }

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $headers = [
            'Content-Type' => 'text/plain',
        ];
        if (is_array($result) || is_object($result)) {
            $headers['Content-Type'] = 'application/json';
            $result = json_encode($result);
        }

        $output = ob_get_clean();
        if ($output) {
            $headers['Content-Type'] = 'text/plain';
            $result = $output . $result;
        }

        return new Response(200, $headers, $result);
    }

    public function process(string $url): ?string
    {
        $method = 'get';
        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        $request = new ServerRequest($method, $url, [], null, '1.1', $_SERVER);

        if (count($_REQUEST)) {
            $request = $request->withParsedBody($_REQUEST);
        }

        $response = $this->handle($request);
        return (string) $response->getBody();
    }

    public function swoole(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        $uri = $swooleRequest->server['request_uri'];
        if (array_key_exists('query_string', $swooleRequest->server)) {
            $uri .= '?' . $swooleRequest->server['query_string'];
        }
        $serverRequest = new ServerRequest(
            $swooleRequest->server['request_method'],
            $uri,
            $swooleRequest->header,
            property_exists($swooleRequest, 'rawContent') ? $swooleRequest->rawContent : null,
            $swooleRequest->server['server_protocol'],
            $swooleRequest->server,
        );

        if (property_exists($swooleRequest, 'post')) {
            if (is_array($swooleRequest->post)) {
                if (count($swooleRequest->post)) {
                    $serverRequest = $serverRequest->withParsedBody($swooleRequest->post);
                }
            }
        }

        $start = microtime(true);
        $response = $this->handle($serverRequest);
        $type = $response->getHeaderLine('Content-Type');

        $log = sprintf(
            "%s %s [%01.3f]",
            $swooleRequest->server['request_method'],
            $swooleRequest->server['request_uri'],
            microtime(true) - $start
        );

        if (microtime(true) - $start <= 0.001) {
            $log = sprintf(
                "%s %s",
                $swooleRequest->server['request_method'],
                $swooleRequest->server['request_uri'],
            );
        }

        $this->get(LoggerInterface::class)->info($log);

        $swooleResponse->status($response->getStatusCode());
        $swooleResponse->header("Content-Type", $type);
        $swooleResponse->end($response->getBody());
    }

    public function error(string $url): string
    {
        return "Invalid request: $url";
    }

    public function match(string $url, string $pattern): bool
    {
        if ($url == $pattern) {
            return true;
        } elseif (strpos($pattern, '*') !== false) {
            $url = explode('/', $url);
            $pattern = explode('/', $pattern);
            $valid = true;
            foreach (range(0, 1) as $part) {
                $valid = $valid && ($pattern[$part] == '*' || $url[$part] == $pattern[$part]);
            }
            return $valid;
        }

        return false;
    }


    public function getChain(string $url): array
    {
        list($clean) = explode('?', $url);
        $chain = [];
        foreach (explode('/', $clean) as $k => $v) {
            if ($v) {
                $chain[] = $v;
            }
        }

        if (!count($chain)) {
            $chain[] = 'index';
        }

        if (count($chain) == 1) {
            $chain[] = 'index';
        }

        return $chain;
    }
}
