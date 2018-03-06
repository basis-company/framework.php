<?php

namespace Entity;

use Tarantool\Mapper\Entity;

class Post extends Entity
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $text;

    public function afterCreate()
    {
        $this->text .= '!';
    }

    public function afterUpdate()
    {
        $this->text .= '.';
    }
}
