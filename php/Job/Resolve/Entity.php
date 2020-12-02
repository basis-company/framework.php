<?php

namespace Basis\Job\Resolve;

use Basis\Toolkit;

class Entity
{
    use Toolkit;

    public string $space;

    public function run()
    {
        $entity = $this->findOrFail('space.entity', [
            'space' => $this->space,
        ]);

        return [
            'id' => $entity->id,
            'expire' => $this->getDate('1 year')->getTimestamp(),
        ];
    }
}
