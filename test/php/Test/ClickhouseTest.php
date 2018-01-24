<?php

namespace Test;

use Basis\Test;
use ClickHouseDB\Client;

class ClickhouseTest extends Test
{
    public function test()
    {
        $clickhouse = $this->get(Client::class);
        $this->assertSame($clickhouse->getConnectHost(), 'test-ch');

        $this->assertSame($this->get(Client::class), $clickhouse);
    }
}
