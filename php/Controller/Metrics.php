<?php

namespace Basis\Controller;

use Basis\Toolkit;
use Basis\Http;
use Basis\Metric\Registry;

class Metrics
{
    use Toolkit;

    public function index(Http $http, Registry $registry)
    {
        $http->setLogging(false);

        return $registry->render('svc_');
    }
}
