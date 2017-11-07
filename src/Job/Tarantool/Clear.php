<?php

namespace Basis\Job\Tarantool;

use Basis\Job;
use Tarantool\Client\Client;
use Tarantool\Client\Schema\Index;

class Clear extends Job
{
    public function run(Client $client)
    {
        $space = $client->getSpace('_vspace');
        $response = $space->select([], Index::SPACE_NAME);
        $data = $response->getData();
        foreach ($data as $row) {
            if ($row[1] == 0) {
                // user space
                $client->evaluate('box.space.'.$row[2].':drop{}');
            }
        }

        $schema = $client->getSpace('_schema')->select([], 0)->getData();
        foreach ($schema as $tuple) {
            if (strpos($tuple[0], 'mapper-once') === 0) {
                $client->getSpace('_schema')->delete([$tuple[0]]);
            }
        }
    }
}
