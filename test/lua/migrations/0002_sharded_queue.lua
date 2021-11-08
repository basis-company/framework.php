return {
    down = function()
        if queue.tube.tester ~= nil then
            queue.tube.tester:drop()
        end
    end,
    up = function()
        require('sharded_queue.api').init()

        queue.create_tube('tester')
    end
}