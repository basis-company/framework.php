<?php

namespace Test;

use Basis\Converter;
use Basis\Test;

class ConverterTest extends Test
{
    public function testArrays()
    {
        $this->validateArray([]);
        $this->validateArray(['name' => 'tester']);
        $this->validateArray(['name' => null]);
        $this->validateArray(['config' => []]);
        $this->validateArray(['config' => ['a' => 1]]);
        $this->validateArray(['config' => ['a' => null]]);
        $this->validateArray(['config' => ['a' => null, 'b' => [1]]]);
        $this->validateArray(['config' => ['a' => null, 'b' => [null]]]);
    }

    private function validateArray($array)
    {
        $object = $this->app->get(Converter::class)->toObject($array);
        $candidate = $this->app->get(Converter::class)->toArray($object);
        $this->assertSame($array, $candidate);
    }

    public function testStrings()
    {
        $converter = $this->app->get(Converter::class);
        $this->assertSame($converter, $this->app->get(Converter::class));

        $this->assertSame('a', $converter->toCamelCase('a'));
        $this->assertSame('A', $converter->toCamelCase('a', true));

        $this->assertSame('personState', $converter->toCamelCase('person_state'));
        $this->assertSame('PersonState', $converter->toCamelCase('person_state', true));

        $this->assertSame('person_role', $converter->toUnderscore('personRole'));
        $this->assertSame('person_role', $converter->toUnderscore('PersonRole'));

        $this->assertSame('test', $converter->toObject(['test' => 'test'])->test);
        $this->assertSame(['gateway', 'audit'], $converter->toObject([
            'names' => ['gateway', 'audit']
        ])->names);
    }
}
