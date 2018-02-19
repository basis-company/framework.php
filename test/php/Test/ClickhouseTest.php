<?php

namespace Test;

use Basis\Clickhouse;
use Basis\Test;
use ClickHouseDB\Client;

class ClickhouseTest extends Test
{
    public function setup()
    {
        parent::setup();
        $client = $this->get(Client::class);

        // recreate test database
        $client->database('default');
        $client->write('drop database test');
        $client->write('create database if not exists test');
        $client->database('test');
    }

    public function testConnectionConfiguration()
    {
        $clickhouse = $this->get(Client::class);
        $this->assertSame($clickhouse->getConnectHost(), '127.0.0.1');
        $this->assertSame($this->get(Client::class), $clickhouse);
    }

    public function testBulkInsert()
    {
        $client = $this->get(Client::class);
        $query = "create table if not exists test.tester (
            date Date,
            time DateTime,
            id String,
            value String
        )
        engine=MergeTree(date, (id), 8192)";

        $client->write($query);

        $headers = ['date', 'time', 'id', 'value'];

        $data = [];
        foreach (range(1, 20) as $i) {
            $data[] = [date('Y-m-d'), time(), "$i", "the $i"];
        }

        $clickhouse = $this->get(Clickhouse::class);
        $clickhouse->bucketSize = 10;

        $buckets = $this->insert('tester', $data, $headers);
        $this->assertSame(2, $buckets);
        $select = $this->select('*', 'tester', []);
        $this->assertCount(20, $select->rows());

        $clickhouse->bucketSize = 100;

        $buckets = $this->insert('tester', $data, $headers);
        $this->assertSame(1, $buckets);

        $select = $this->select('*', 'tester', []);
        $this->assertCount(40, $select->rows());
    }

    public function testSelect()
    {
        $client = $this->get(Client::class);
        $query = "create table if not exists test.tester (
            date Date,
            time DateTime,
            id String,
            value String
        )
        engine=MergeTree(date, (id), 8192)";

        $client->write($query);

        $headers = ['date', 'time', 'id', 'value'];

        $data = [
            [date('Y-m-d'), time(), "1", "nekufa"],
            [date('Y-m-d'), time(), "2", "petya"],
        ];

        $this->insert('tester', $data, $headers);

        $select = $this->select('*', 'tester', ['id' => 1]);
        $this->assertCount(1, $select->rows());

        $select = $this->select('*', 'tester', ['id' => "2"]);
        $this->assertCount(1, $select->rows());

        $select = $this->select('*', 'tester', []);
        $this->assertCount(2, $select->rows());
    }
}
