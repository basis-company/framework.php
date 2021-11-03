return {
    down = function()
        box.space.my_sharded_space:drop()
        box.space._ddl_sharding_key:delete('my_sharded_space')
        return true
    end,
    up = function()
        local utils = require('migrator.utils')
        local f = box.schema.create_space('my_sharded_space', {
            format = {
                { name = 'key', type = 'string' },
                { name = 'bucket_id', type = 'unsigned' },
                { name = 'value', type = 'any', is_nullable = true }
            },
            if_not_exists = true,
        })
        f:create_index('primary', {
            parts = { 'key' },
            if_not_exists = true,
        })
        f:create_index('bucket_id', {
            parts = { 'bucket_id' },
            if_not_exists = true,
            unique = false
        })
        utils.register_sharding_key('my_sharded_space', {'key'})
        return true
    end
}