<?php

namespace Test;

use Basis\Test;

class AttributesTest extends Test
{
    public $data = [
        'entity.attribute' => [],
        'entity.attribute_value' => [],
        'space.entity' => [],
    ];

    public function testBasics()
    {
        $this->create('space.entity', [ 'space' => 'guard.access' ]);
        $this->create('space.entity', [ 'space' => 'guard.login' ]);

        $empty = $this->getAttributes('guard.login', 1);
        $this->assertEquals([], $empty);

        $defaults = $this->getAttributes('guard.login', 1, [
            'redirect' => '0',
        ]);

        $this->assertEquals($defaults, [
            'redirect' => '0',
        ]);

        $this->setAttributes('guard.login', 1, [ 'redirect' => '1' ]);

        $attributes = $this->getAttributes('guard.login', 1, [
            'redirect' => '0',
        ]);

        $this->assertEquals($attributes, [
            'redirect' => '1',
        ]);

        $merged = $this->getAttributes('guard.login', 1, [
            'lock' => '1',
        ]);

        $this->assertEquals($merged, [
            'redirect' => '1',
            'lock' => '1',
        ]);

        $byKey = $this->getAttributes('guard.login', 2, [
            'lock' => '1',
        ]);

        $this->assertEquals($byKey, [
            'lock' => '1',
        ]);

        $byEntity = $this->getAttributes('guard.access', 1, [
            'lock' => '1',
        ]);

        $this->assertEquals($byEntity, [
            'lock' => '1',
        ]);
    }
}
