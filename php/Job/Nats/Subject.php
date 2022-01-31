<?php

namespace Basis\Job\Nats;

use Basis\Cache;
use Basis\Nats\Client;

class Subject
{
    public $params;
    public string $job;
    public string $service;

    public int $ttl = 600; // 10 minutes

    public function __construct(
        public readonly Cache $cache,
        public readonly Client $client,
    ) {
    }

    public function run()
    {
        // default service subject
        [$subject] = explode('.', $this->job);

        $params = [];
        if (is_object($this->params)) {
            $params = get_object_vars($this->params);
        }

        foreach ($this->getCandidates() as $candidate) {
            if ($candidate->job !== $this->job) {
                continue;
            }
            if (!property_exists($candidate, 'params')) {
                $subject = $candidate->subject;
                break;
            }

            $candidateParams = get_object_vars($candidate->params);

            if (array_intersect($candidateParams, $params) == $candidateParams) {
                $subject = $candidate->subject;
                break;
            }
        }

        return compact('subject');
    }

    public function getCandidates(): array
    {
        $cached = $this->cache->wrap($this->job, function () {
            $result = (object) [
                'expire' => time() - $this->ttl,
                'config' => [],
            ];

            $json = $this->client->getApi()->getBucket('service_handlers')
                ->get('job_' . str_replace('.', '_', $this->job));

            if ($json) {
                $result->config = json_decode($json) ?: [];
            }

            return $result;
        });

        return $cached->config;
    }
}
