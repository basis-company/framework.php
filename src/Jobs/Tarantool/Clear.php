<?php

namespace Basis\Jobs\Tarantool;

use Tarantool\Client;
use Tarantool\Schema\Index;

class Clear
{
    public function run(Client $client)
    {
        $schema = $client->getSpace('_vspace');
        $response = $schema->select([], Index::SPACE_NAME);
        $data = $response->getData();
        foreach ($data as $row) {
            if ($row[1] == 0) {
                // user space
                $client->evaluate('box.space.'.$row[2].':drop{}');
            }
        }
    }
}
