<?php

namespace Basis\Job\Module;

use Exception;

class Trigger extends Call
{
    public string $space;
    public int $id;
    public string $type;

    public function run()
    {
        $this->require('space');
        $this->require('id');

        if (!in_array($this->type, ['create', 'update', 'remove'])) {
            throw new Exception("Invalid type $this->type");
        }

        $method = 'after' . ucfirst($this->type);

        $entity = $this->findOrFail($this->space, $this->id);

        // afterUpdate trigger will be called on save
        if ($this->type != 'update') {
            if (method_exists($entity, $method)) {
                $this->method = $method;
                parent::run();
            }
        }

        $entity->save();
    }
}
