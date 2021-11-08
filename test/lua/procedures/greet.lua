function (name)
    return 'hello, ' .. (name or box.schema.func.call('default_username')) .. '!'
end