<?php

namespace Test;

use Carbon\Carbon;
use Basis\Converter;
use Basis\Test;

class ConverterTest extends Test
{
    public function testPlural()
    {
        $converter = $this->get(Converter::class);
        $forms = ['яблоко', 'яблока', 'яблок'];

        $this->assertSame($converter->getPluralForm(0, $forms), 'яблок');
        $this->assertSame($converter->getPluralForm(1, $forms), 'яблоко');
        $this->assertSame($converter->getPluralForm(2, $forms), 'яблока');
        $this->assertSame($converter->getPluralForm(5, $forms), 'яблок');
        $this->assertSame($converter->getPluralForm(21, $forms), 'яблоко');
        $this->assertSame($converter->getPluralForm(23, $forms), 'яблока');
        $this->assertSame($converter->getPluralForm(25, $forms), 'яблок');
        $this->assertSame($converter->getPluralForm(1.25, $forms), 'яблока');
    }

    public function testDates()
    {
        $this->assertSame(time(), $this->get(Converter::class)->getTimestamp('now'));

        $midnight = $this->get(Converter::class)->getTimestamp(date('Ymd'));
        $constructed = $this->get(Converter::class)->getDate(+date('Y'), +date('m'), +date('d'));

        $this->assertSame($midnight, $constructed->timestamp);

        $toolkited = $this->getDate(+date('Y'), +date('m'), +date('d'));
        $this->assertSame($midnight, $toolkited->timestamp);

        // carbon test tune
        $yesterday = Carbon::parse('yesterday');
        Carbon::setTestNow($yesterday);
        $this->assertSame(
            $yesterday->timestamp,
            $this->get(Converter::class)->getTimestamp('now')
        );

        $this->assertSame(
            $midnight,
            $this->get(Converter::class)->getDate('tomorrow')->timestamp
        );
    }

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

        $this->assertEquals($this->get(Converter::class)->toObject((object) ['q' => 1]), (object) [
            'q' => 1
        ]);

        $this->assertTrue($this->app->get(Converter::class)->isTuple((object) []));
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
