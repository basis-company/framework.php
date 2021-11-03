<?php

namespace Basis\Job\Data;

use Basis\Data;
use Basis\Registry;
use Basis\Toolkit;

class Clear
{
    use Toolkit;

    public function run(Data $data, Registry $registry)
    {
        if (!$this->dispatch('data.analyze')->present) {
            return ['msg' => 'not present'];
        }

        foreach ($registry->listFiles('lua/migrations') as $migration) {
            $contents = file_get_contents('lua/migrations/' . $migration);

            $script = self::ROLLBACK_MIGRATION;
            $script = str_replace('BODY', $contents, $script);

            $data->getClient()->evaluate($script);
        }

        $data->getClient()->evaluate(self::ROLLBACK_APPLIED_MIGRATIONS);
    }

    private const ROLLBACK_MIGRATION = <<<LUA
        local source = function() BODY end
        source().down()
    LUA;

    private const ROLLBACK_APPLIED_MIGRATIONS = <<<LUA
        require('cartridge').config_patch_clusterwide({ migrations = { applied = {}}})
    LUA;
}
