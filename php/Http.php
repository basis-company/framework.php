<?php

namespace Basis;

use Basis\Controller\Rest;
use Exception;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Http
{
    use Toolkit;

    private ?array $mapping = null;
    private bool $logging = true;

    public function getLogging(): bool
    {
        return $this->logging;
    }

    public function setLogging(bool $logging): self
    {
        $this->logging = $logging;
        return $this;
    }

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
                    if ($registry->hasTrait($class, Toolkit::class) && in_array($name, $toolkit)) {
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
        $routes = [];

        foreach ($this->getMapping() as $route => [ $class ]) {
            if (strpos($class, 'Basis\\') === false) {
                $routes[] = $route;
            }
        }

        return $routes;
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
        $handleStart = microtime(true);

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
            $result = $container->get(Rest::class)->process($request);
            if (!$result) {
                $result = "Page not found: $uri";
            }
        }

        if ($this->logging) {
            $message = $request->getMethod() . ' ' . $request->getUri()->getPath();

            $context = [];
            $time = microtime(true) - $handleStart;
            if ($time >= 0.001) {
                $context['time'] = round($time, 3);
            }

            $this->get(LoggerInterface::class)->info($message, $context);
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
        if (function_exists('apache_request_headers') && count(apache_request_headers())) {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            $request = $creator->fromGlobals();
        } else {
            $method = 'get';
            if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
                $method = $_SERVER['REQUEST_METHOD'];
            }

            $request = new ServerRequest($method, $url, [], null, '1.1', $_SERVER);

            if (count($_REQUEST)) {
                $request = $request->withParsedBody($_REQUEST);
            }
        }

        $response = $this->handle($request);

        if (!headers_sent()) {
            header("HTTP/1.1 " . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
            foreach ($response->getHeaders() as $k => $rows) {
                foreach ($rows as $row) {
                    header("$k: $row");
                }
            }
        }

        return (string) $response->getBody();
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
