<?php

namespace Basis\Job\Data;

use Basis\Data\Master;
use Basis\Registry;
use Basis\Toolkit;

class Migrate
{
    use Toolkit;

    public function run(Master $master, Registry $registry)
    {
        if (!$this->dispatch('data.analyze')->present) {
            return ['msg' => 'not present'];
        }

        $migrations = [];

        foreach ($registry->listFiles('lua/migrations') as $migration) {
            $contents = file_get_contents('lua/migrations/' . $migration);
            $name = pathinfo($migration)['filename'];
            $script = self::MIGRATION_ROW;
            $script = str_replace('BODY', $contents, $script);
            $script = str_replace('NAME', $name, $script);
            $migrations[] = $script;
        }

        $script = self::APPLY_MIGRATIONS;
        $script = str_replace('MIGRATIONS', implode("\n\r", $migrations), $script);

        $master->getWrapper()->getClient()->evaluate($script);

        foreach ($registry->listFiles('lua/procedures') as $procedure) {
            $contents = file_get_contents('lua/procedures/' . $procedure);
            $name = pathinfo($procedure)['filename'];

            $script = self::CREATE_FUNCTION;
            $script = str_replace('BODY', $contents, $script);
            $script = str_replace('NAME', $name, $script);
            $script = str_replace('HASH', md5($contents), $script);

            $master->getWrapper()->getClient()->evaluate($script);
        }
    }

    private const APPLY_MIGRATIONS = <<<LUA
        local servers = require('cartridge').config_get_readonly('topology').servers
        for i, server in pairs(servers) do
            require('cartridge.pool').connect(server.uri):eval([[
                local migrator = require('migrator')
                migrator.set_loader({
                    list = function(_)
                        return {
                            MIGRATIONS
                        }
                    end
                })
            ]])
        end

        local migrator = require('migrator')
        migrator.set_use_cartridge_ddl(false)
        migrator.up()
    LUA;

    private const CREATE_FUNCTION = <<<LUA
        local topology = require('cartridge').config_get_readonly('topology')
        for i, server in pairs(topology.servers) do
            require('cartridge.pool').connect(server.uri):eval([[
                NAME = BODY
            ]])
        end

        for i, replicaset in pairs(topology.replicasets) do
            local connection = require('cartridge.pool').connect(topology.servers[replicaset.master[1]].uri)
            local version = connection.space._schema:get('NAME')
            if version and version.value == 'HASH' then
                return
            end

            if connection:call('box.schema.func.exists', {'NAME'}) then
                connection:call('box.schema.func.drop', {'NAME'})
            end

            connection:call('box.schema.func.create', { 'NAME', {
                if_not_exists = true
            }})

            connection.space._schema:replace({'NAME', 'HASH'})
        end
    LUA;

    private const MIGRATION_ROW = <<<LUA
        {
            name  = 'NAME',
            up = function()
                local source = function()
                    BODY
                end
                return source().up()
            end
        },
    LUA;
}
