<?php

namespace Basis\Procedure;

use Tarantool\Mapper\Procedure;

class Select extends Procedure
{
    public function getParams() : array
    {
        return ['space', 'index', 'values'];
    }

    public function getBody(): string
    {
        return <<<LUA
        if box.space[space] == nil then
            return nil
        end
        if box.space[space].index[index] == nil then
            return nil
        end

        if #values == 0 then
            return {}
        end

        local pk = {}
        for i, t in pairs(box.space[space].index[0].parts) do
            table.insert(pk, t.fieldno)
        end

        local result = {}
        local keys = {}

        for i, value in pairs(values) do
            for j, tuple in box.space[space].index[index]:pairs(value) do
                local key = ""
                for i, f in pairs(pk) do
                    key = key .. tuple[f] .. '-'
                end
                if keys[key] == nil then
                    keys[key] = true
                    table.insert(result, tuple)
                end
            end
        end
        return result
LUA;
    }
}
