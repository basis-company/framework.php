<?php

namespace Test;

use Basis\Cache;
use Basis\Test;

class CacheTest extends Test
{
    public function test()
    {
        $cache = $this->get(Cache::class);
        $cache->set('username', 'nekufa');

        $this->assertSame($cache->get('username'), 'nekufa');

        $cache->clear();
        $this->assertNull($cache->get('username'));
    }
}
