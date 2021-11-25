<?php

namespace Basis\Data;

use Exception;

class Crud
{
    public function __construct(private Wrapper $wrapper, private string $space)
    {
    }

    public function getWrapper(): Wrapper
    {
        return $this->wrapper;
    }

    public function getSpace(): string
    {
        return $this->space;
    }

    public function delete(array|int|string $key, array $opts = []): ?array
    {
        return $this->unflatten('crud.delete', $this->getSpace(), $key, $opts);
    }

    public function get(array|int|string $key, array $opts = []): ?array
    {
        return $this->unflatten('crud.get', $this->getSpace(), $key, $opts);
    }

    public function insert(array $data): array
    {
        return $this->unflatten('crud.insert_object', $this->getSpace(), $data);
    }

    public function replace(array $data): array
    {
        return $this->unflatten('crud.replace_object', $this->getSpace(), $data);
    }

    public function update(array|int|string $key, array $operations = []): ?array
    {
        return $this->unflatten('crud.update', $this->getSpace(), $key, $operations);
    }

    public function upsert(array $data, array $operations = []): ?array
    {
        return $this->unflatten('crud.upsert_object', $this->getSpace(), $data, $operations);
    }

    protected function unflatten($function, ...$args): ?array
    {
        $response = $this->getWrapper()->getClient()->call($function, ...$args);

        [$result, $err] = $response;

        if ($err) {
            if (is_array($err) && array_key_exists('str', $err)) {
                throw new Exception($err['str']);
            }

            throw new Exception($err);
        }

        if (!array_key_exists('rows', $result)) {
            throw new Exception("Invalid result");
        }

        if (count($result['rows']) == 0) {
            return null;
        }

        $keys = [];
        foreach ($result['metadata'] as $meta) {
            $keys[] = $meta['name'];
        }

        $instances = [];
        foreach ($result['rows'] as $tuple) {
            $instances[] = array_combine($keys, $tuple);
        }

        if (count($instances) == 1) {
            return $instances[0];
        }

        return $instances;
    }
}
