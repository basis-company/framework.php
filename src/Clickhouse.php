<?php

namespace Basis;

use ClickHouseDB\Client;

class Clickhouse
{
    use Toolkit;

    public $bucketSize = 1000;

    public function select($fields, string $table, array $params = [])
    {
        if (is_array($fields)) {
            $fields = implode(', ', $fields);
        }

        $query = "SELECT $fields FROM $table";
        $binds = [];

        if (count($params)) {
            $where = [];
            foreach ($params as $k => $v) {
                $binds[$k] = (array) $v;
                $where[] = $k.' in (:'.$k.')';
            }

            $where = implode(' and ', $where);

            $query .= " where $where";
        }

        return $this->get(Client::class)->select($query, $binds);
    }

    public function insert(string $table, array $data, array $headers)
    {
        if (count($data) < $this->bucketSize) {
            $buckets = [$data];
        } else {
            $buckets = array_chunk($data, $this->bucketSize);
        }

        $client = $this->get(Client::class);
        foreach ($buckets as $bucket) {
            $client->insert('tester', $bucket, $headers);
        }

        return count($buckets);
    }
}
