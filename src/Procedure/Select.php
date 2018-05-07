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

        local parentField = nil

        for i, f in pairs(box.space[space]:format()) do
            if f.name == 'parent' and f.reference == space then
                parentField = i
            end
        end

        local childIndex = nil

        if parentField ~= nil then
            for i, candidate in pairs(box.space[space].index) do
                if #candidate.parts == 1 then
                    if candidate.parts[1].fieldno == parentField then
                        childIndex = candidate.name
                    end
                end
            end
        end

        local function selector(index, value)
            for j, tuple in box.space[space].index[index]:pairs(value) do
                local key = ""
                for i, f in pairs(pk) do
                    key = key .. tuple[f] .. '-'
                end
                if keys[key] == nil then
                    keys[key] = true
                    table.insert(result, tuple)
                    if childIndex ~= nil then
                        selector(childIndex, tuple[1])
                    end
                end
            end

        end

        for i, value in pairs(values) do
            selector(index, value)
        end

        return result
LUA;
    }
}
