<?php

namespace Basis\Feedback;

class Info extends Feedback
{
    public function getHashes(): array
    {
        return [ $this->getHash() ];
    }

    public function getValue(string $hash)
    {
        return $this->getHash() == $hash;
    }

    public function getHash(): string
    {
        return md5($this->getMessage());
    }

    public function serialize(): array
    {
        return [
            'type' => 'info',
            'hash' => $this->getHash(),
            'message' => $this->getMessage(),
        ];
    }
}
