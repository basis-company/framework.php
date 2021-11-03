<?php

namespace Basis\Job\Data;

use Basis\Data;
use Basis\Registry;
use Basis\Toolkit;

class Migrate
{
    use Toolkit;

    public function run(Data $data, Registry $registry)
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

        $data->getClient()->evaluate($script);
    }

    private const APPLY_MIGRATIONS = <<<LUA
        local migrator = require('migrator')
        migrator.set_loader({
            list = function(_)
                return {
                    MIGRATIONS
                }
            end
        })
        migrator.set_use_cartridge_ddl(false)
        migrator.up()
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
