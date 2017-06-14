<?php

namespace Entity;

use Tarantool\Mapper\Entity as MapperEntity;

class Note extends MapperEntity
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
