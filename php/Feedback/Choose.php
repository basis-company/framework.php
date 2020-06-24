<?php

namespace Basis\Feedback;

class Choose extends Feedback
{
    protected array $options;

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getHashes(): array
    {
        return array_keys($this->getOptionsMap());
    }

    public function getValue(string $hash)
    {
        $map = $this->getOptionsMap();
        if (array_key_exists($hash, $map)) {
            return $map[$hash];
        }
    }

    public function serialize(): array
    {
        return [
            'type' => 'choose',
            'message' => $this->getMessage(),
            'options' => $this->getOptionsMap(),
        ];
    }

    public function getOptionsMap(): array
    {
        $message = $this->getMessage();
        $map = [];
        foreach ($this->options as $value) {
            $key = md5($message . $value);
            $map[$key] = $value;
        }
        return $map;
    }
}
