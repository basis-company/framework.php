<?php

namespace Test;

use Basis\Filesystem;
use Basis\Job;
use Basis\Test;

class TestingTest extends TestSuite
{
    public function test()
    {
        $userTest = new class($this->app) extends Test {
            public function testMock($frameworkTest)
            {
                $config = [
                    [['name' => 'nekufa'], ['value' => 'nekufa!']],
                    [['name' => 'bazyaba'], function () {
                        return ['value' => 'bazyaba!'];
                    }],
                    [[], ['value' => 'anonymous!']],
                ];
                foreach ($config as [$params, $value]) {
                    $this->mock('service.call', $params)->willReturn($value);
                }

                foreach ($config as [$params, $value]) {
                    $result = $this->dispatch('service.call', $params);
                    if (is_callable($value)) {
                        $value = $value();
                    }
                    $frameworkTest->assertSame(get_object_vars($result), $value);
                }
            }
        };

        $userTest->setup();
        $userTest->testMock($this);
    }
}
