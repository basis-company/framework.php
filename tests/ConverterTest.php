<?php

use Basis\Converter;

class ConverterTest extends TestSuite
{
    public function test()
    {
        $converter = $this->app->get(Converter::class);
        $this->assertSame($converter, $this->app->get(Converter::class));

        $this->assertSame('a', $converter->toCamelCase('a'));
        $this->assertSame('A', $converter->toCamelCase('a', true));

        $this->assertSame('personState', $converter->toCamelCase('person_state'));
        $this->assertSame('PersonState', $converter->toCamelCase('person_state', true));

        $this->assertSame('person_role', $converter->toUnderscore('personRole'));
        $this->assertSame('person_role', $converter->toUnderscore('PersonRole'));
    }
}
