<?php

namespace Basis\Job\Resolve;

use Basis\Toolkit;

class Space
{
    use Toolkit;

    public int $entity;

    public function run()
    {
        $entity = $this->findOrFail('space.entity', [
            'id' => $this->entity,
        ]);

        return [
            'name' => $entity->space,
            'expire' => $this->getDate('1 year')->getTimestamp(),
        ];
    }
}
