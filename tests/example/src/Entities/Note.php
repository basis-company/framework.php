<?php

namespace Entities;

use Tarantool\Mapper\Entity;

class Note extends Entity
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $message;
}
