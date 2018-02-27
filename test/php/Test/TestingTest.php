<?php

namespace Test;

use Tarantool\Mapper\Pool;
use Basis\Test;

class TestingTest extends Test
{
    public $mocks = [
        ['say.hello', ['name' => 'nekufa'], ['text' => 'Hola, nekufa']],
        ['say.hello', ['name' => 'vasiliy'], ['text' => 'Vasya, privet!']],
        ['web.services', [], ['services' => ['web', 'tester', 'basis']]]
    ];

    public $data = [
        'web.services' => [
            ['id' => 1, 'name' => 'tester'],
            ['id' => 2, 'name' => 'basis'],
            ['id' => 3, 'name' => 'web'],
        ],
        'tester.data' => [
            ['id' => 3, 'value' => 'test'],
            ['id' => 4, 'value' => 'test'],
        ],
    ];

    public function testMagicProperties()
    {
        foreach ($this->mocks as [$job, $params, $result]) {
            $jobResult = get_object_vars($this->dispatch($job, $params));
            $this->assertSame($jobResult, $result);
        }
    }

    public function testPool()
    {
        $web = $this->get(Pool::class)->get('web');
        $services = $web->find('services');
        $this->assertCount(3, $services);
        $this->assertCount(1, $web->find('services', ['name' => 'web']));

        $tester = $this->get(Pool::class)->get('tester');
        $this->assertCount(0, $tester->find('data', ['id' => 2]));
        $this->assertCount(1, $tester->find('data', ['id' => 3]));
        $this->assertCount(2, $tester->find('data', ['value' => 'test']));
    }
}
