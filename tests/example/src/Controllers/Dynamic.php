<?php

namespace Example\Controllers;

class Dynamic
{
    function __process($url)
    {
        return "url: $url";
    }
}