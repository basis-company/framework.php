<?php

namespace Basis\Configuration;

use Basis\Container;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\Cache\Traits\FilesystemTrait;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\PruneableInterface;

class Cache
{
    public function init(Container $container)
    {
        $container->share(ArrayAdapter::class, function () {
            return new ArrayAdapter();
        });

        $container->share(FilesystemAdapter::class, function () {
            return new class extends AbstractAdapter implements PruneableInterface {
                use FilesystemTrait;

                public function __construct(string $namespace = '', int $defaultLifetime = 0)
                {
                    $this->marshaller = new DefaultMarshaller();
                    parent::__construct('', $defaultLifetime);
                    $this->init($namespace);
                }

                private function init(string $namespace)
                {
                    $directory = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'symfony-cache';
                    if (strlen($namespace)) {
                        if (preg_match('#[^-+_.A-Za-z0-9]#', $namespace, $match)) {
                            $msg = 'Namespace contains "%s" but only characters in [-+_.A-Za-z0-9] are allowed.';
                            throw new InvalidArgumentException(sprintf($msg, $match[0]));
                        }
                        $directory .= \DIRECTORY_SEPARATOR . $namespace . \DIRECTORY_SEPARATOR;
                    } else {
                        $directory .= \DIRECTORY_SEPARATOR . '@' . \DIRECTORY_SEPARATOR;
                    }
                    if ('\\' === \DIRECTORY_SEPARATOR && \strlen($directory) > 234) {
                        throw new InvalidArgumentException(sprintf('Cache directory too long (%s).', $directory));
                    }
                    $this->makeDir($directory);
                    $this->directory = $directory;
                }

                private function write(string $file, string $data, int $expiresAt = null)
                {
                    set_error_handler(__CLASS__ . '::throwError');
                    try {
                        if (null === $this->tmp) {
                            $this->tmp = $this->directory . bin2hex(random_bytes(6));
                        }
                        try {
                            $h = fopen($this->tmp, 'x');
                        } catch (\ErrorException $e) {
                            if (!str_contains($e->getMessage(), 'File exists')) {
                                throw $e;
                            }
                            $this->tmp = $this->directory . bin2hex(random_bytes(6));
                            $h = fopen($this->tmp, 'x');
                        }
                        fwrite($h, $data);
                        fclose($h);

                        if (null !== $expiresAt) {
                            touch($this->tmp, $expiresAt ?: time() + 31556952);
                        }

                        $result = rename($this->tmp, $file);
                        @chmod($file, 0777);
                        return $result;
                    } finally {
                        restore_error_handler();
                    }
                }

                private function getFile(string $id, bool $mkdir = false, string $directory = null)
                {
                    $base = $directory ?? $this->directory;
                    $hash = str_replace('/', '-', base64_encode(hash('md5', static::class . $id, true)));
                    $dir = $base . strtoupper($hash[0] . \DIRECTORY_SEPARATOR . $hash[1] . \DIRECTORY_SEPARATOR);

                    if ($mkdir) {
                        $this->makeDir($dir);
                    }
                    return $dir . substr($hash, 2, 20);
                }

                private function makeDir($directory)
                {
                    $directory = rtrim($directory, \DIRECTORY_SEPARATOR);

                    if (!is_dir($directory)) {
                        @mkdir($directory, 0777, true);
                    }

                    while (strlen($directory) && $directory != '/' && $directory != '.') {
                        @chmod($directory, 0777);
                        $directory = dirname($directory);
                    }
                }
            };
        });

        if (getenv('BASIS_ENVIRONMENT') === 'testing') {
            $container->share(AdapterInterface::class, ArrayAdapter::class);
        } else {
            $container->share(AdapterInterface::class, function () use ($container) {
                return new ChainAdapter([
                    $container->get(ArrayAdapter::class),
                    $container->get(FilesystemAdapter::class),
                ]);
            });
        }
    }
}
