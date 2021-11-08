<?php

namespace Basis\Data;

class Procedure
{
    public function __construct(private Wrapper $wrapper, private string $name)
    {
    }

    public function getWrapper(): Wrapper
    {
        return $this->wrapper;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __invoke()
    {
        $result = $this->getWrapper()
            ->getClient()
            ->call($this->getName(), ...func_get_args());

        if (count($result) > 1) {
            return $result;
        }

        return $result[0];
    }
}
