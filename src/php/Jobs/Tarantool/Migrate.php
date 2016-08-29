<?php

namespace Basis\Jobs\Tarantool;

use Tarantool\Mapper\Manager;
use Tarantool\Mapper\Migrations\Migrator;

class Migrate
{
    public function run(Manager $manager, Migrator $migrator)
    {
        $migrator->migrate($manager);
    }
}