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

    public function testDispatcherCache()
    {
        $random1 = $this->dispatch('test.random');
        $random2 = $this->dispatch('test.random');
        $this->assertSame($random1->value, $random2->value);

        $this->get(Cache::class)->clear();

        $random2 = $this->dispatch('test.random');
        $this->assertNotSame($random1->value, $random2->value);

        $random1 = $this->dispatch('test.random');
        $this->assertSame($random1->value, $random2->value);
    }
}
