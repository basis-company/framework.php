<?php

namespace Basis\Job\Data;

use Basis\Data\Master;
use Basis\Registry;
use Basis\Toolkit;

class Clear
{
    use Toolkit;

    public function run(Master $master, Registry $registry)
    {
        if (!$this->dispatch('data.analyze')->present) {
            return ['msg' => 'not present'];
        }

        foreach ($registry->listFiles('lua/migrations') as $migration) {
            $contents = file_get_contents('lua/migrations/' . $migration);

            $script = self::ROLLBACK_MIGRATION;
            $script = str_replace('BODY', $contents, $script);

            $master->getWrapper()->getClient()->evaluate($script);
        }

        $master->getWrapper()->getClient()->evaluate(self::ROLLBACK_APPLIED_MIGRATIONS);

        foreach ($registry->listFiles('lua/procedures') as $procedure) {
            $contents = file_get_contents('lua/procedures/' . $procedure);
            $name = pathinfo($procedure)['filename'];

            $script = self::DROP_FUNCTION;
            $script = str_replace('NAME', $name, $script);
            $script = str_replace('HASH', md5($contents), $script);

            $master->getWrapper()->getClient()->evaluate($script);
        }
    }

    private const DROP_FUNCTION = <<<LUA
        local topology = require('cartridge').config_get_readonly('topology')
        for i, replicaset in pairs(topology.replicasets) do
            local connection = require('cartridge.pool').connect(topology.servers[replicaset.master[1]].uri)
            if connection:call('box.schema.func.exists', {'NAME'}) then
                connection:call('box.schema.func.drop', {'NAME'})
            end
            connection.space._schema:delete('NAME')
        end

    LUA;

    private const ROLLBACK_MIGRATION = <<<LUA
        local servers = require('cartridge').config_get_readonly('topology').servers
        for i, server in pairs(servers) do
            require('cartridge.pool').connect(server.uri):eval([[
                local source = function() BODY end
                source().down()
            ]])
        end
    LUA;

    private const ROLLBACK_APPLIED_MIGRATIONS = <<<LUA
        require('cartridge').config_patch_clusterwide({ migrations = { applied = {}}})
    LUA;
}
