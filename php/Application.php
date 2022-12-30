<?php

namespace Basis;

use Basis\Registry\Reflection;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class Application
{
    use Toolkit;

    protected self $app;
    protected string $name;
    protected string $root;

    public function __construct(string $root = null, string $name = null)
    {
        if (!$root) {
            $root = getcwd();
        }
        if (!$name) {
            $name = getenv('SERVICE_NAME');
        }

        $this->app = $this;
        $this->name = $name;
        $this->root = $root;

        $converter = new Converter();
        $registry = new Reflection($this, $converter);

        $this->container = (new Container($registry))
            ->share(Converter::class, $converter)
            ->share(self::class, $this)
            ->share(static::class, $this);

        foreach ($registry->listClasses('configuration') as $class) {
            $this->container->call($class, 'init');
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function getToken()
    {
        $cached = $this->get(Cache::class)->wrap('service-token', function () {
            $token = $this->findOrFail('guard.token', ['service' => $this->getName()])->token;
            $expire = $this->getTokenPayload($token)->exp - 60;
            return (object) compact('expire', 'token');
        });

        return $cached->token;
    }

    public function getTokenPayload(string $token): object
    {
        $cached = $this->get(Cache::class)->wrap('public-key', function () {
            if (file_exists('resources/jwt/public')) {
                $key = file_get_contents('resources/jwt/public');
            } else {
                $key = file_get_contents('http://guard/guard/key');
            }
            if (!$key) {
                throw new Exception("Key calculation failure");
            }
            $expire = time() + 365 * 24 * 60 * 60;
            return (object) compact('expire', 'key');
        });

        return JWT::decode($token, new Key($cached->key, 'RS256'));
    }
}
