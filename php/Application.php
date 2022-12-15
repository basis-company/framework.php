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
        try {
            if (!file_exists('token.php')) {
                throw new Exception("Initialize", 1);
            }

            $token = include 'token.php';
            $payload = $this->getTokenPayload($token);
            if ($token->iat < time() + 60) {
                throw new Exception("Token is about to expire", 1);
            }
        } catch (Throwable $e) {
            $token = $this->findOrFail('guard.token', ['service' => $this->getName()])->token;
            file_put_contents('token.php', '<?php return "' . $token . '";');
        }

        return $token;
    }

    public function getTokenPayload(string $token): object
    {
        $key = null;
        if (file_exists('resources/jwt/public')) {
            // guard
            $key = file_get_contents('resources/jwt/public');
        } elseif (file_exists('key')) {
            // cached key
            $key = file_get_contents('key');
        } else {
            // others
            $key = file_get_contents('http://guard/guard/key');
            if ($key) {
                file_put_contents('key', $key);
            }
        }

        if (!$key) {
            throw new Exception("Key calculation failure");
        }

        return JWT::decode($token, new Key($key, 'RS256'));
    }
}
