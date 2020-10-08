<?php

namespace Basis\Controller;

use Basis\Http;
use Basis\Metric\Registry;

class Metrics
{
    public function index(Http $http, Registry $registry)
    {
        $http->setLogging(false);

        return $registry->render('svc_');
    }
}
