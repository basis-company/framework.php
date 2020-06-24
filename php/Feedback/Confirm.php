<?php

namespace Basis\Feedback;

class Confirm extends Feedback
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
            'type' => 'confirm',
            'hash' => $this->getHash(),
            'message' => $this->getMessage(),
        ];
    }
}
