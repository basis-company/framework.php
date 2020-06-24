<?php

namespace Basis\Job\Tarantool;

use Basis\Cache;
use Tarantool\Client\Client;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\IndexIds;

class Clear
{
    public function run(Client $client, Cache $cache)
    {
        $space = $client->getSpace('_vspace');

        $client->evaluate("
            if box.space._queue ~= nil then
                if queue == nil then
                    queue = require('queue')
                end
                for i, q in box.space._queue:pairs() do
                    queue.tube[q.tube_name]:drop()
                end
            end
        ");
        
        $data = $space->select(Criteria::key([]));

        foreach ($data as $row) {
            if ($row[0] >= 512) {
                // user space
                if (strpos($row[2], '_queue') !== 0) {
                    $client->evaluate('box.space["' . $row[2] . '"]:drop()');
                }
            }
        }

        $sequences = $client->getSpace('_sequence')->select(Criteria::key([]));
        foreach ($sequences as $sequence) {
            $client->evaluate('box.sequence["' . $sequence[2] . '"]:drop()');
        }

        $schema = $client->getSpace('_schema')->select(Criteria::key([]));
        foreach ($schema as $tuple) {
            if (strpos($tuple[0], 'mapper-once') === 0) {
                $client->getSpace('_schema')->delete([$tuple[0]]);
            }
        }

        // $filename = $fs->getPath('.cache/mapper-meta.php');
        // if (file_exists($filename)) {
        //     unlink($filename);
        // }
    }
}
