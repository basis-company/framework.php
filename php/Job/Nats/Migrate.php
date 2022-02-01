<?php

namespace Basis\Job\Nats;

use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Nats\Stream\DiscardPolicy;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\StorageBackend;

class Migrate
{
    public function run(Dispatcher $dispatcher, Client $client)
    {
        $jobs = [];

        foreach ($dispatcher->getHandlers() as $handler) {
            $stream = $client->getApi()->getStream($handler['subject']);
            $stream->getConfiguration()
                   ->setDiscardPolicy(DiscardPolicy::NEW)
                   ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
                   ->setStorageBackend(StorageBackend::FILE)
                   ->setSubjects([$handler['subject']]);

            if (!$stream->exists()) {
                $stream->create();
            } else {
                $stream->update();
            }

            $consumer = $stream->getConsumer($handler['subject']);
            $consumer->getConfiguration()
                ->setSubjectFilter($handler['subject']);

            if (array_key_exists('threads', $handler)) {
                $consumer->getConfiguration()
                    ->setMaxAckPending($handler['threads']);
            }

            if (!$consumer->exists()) {
                $consumer->create();
            }

            if (array_key_exists('job', $config)) {
                if (!array_key_exists($config['job'], $jobs)) {
                    $jobs[$config['job']] = [];
                }
                $jobs[$config['job']][] = $config;
            }
        }

        $bucket = $client->getApi()->getBucket('service_handlers');
        foreach ($jobs as $job => $config) {
            $bucket->put('job_' . str_replace('.', '_', $job), json_encode($config));
        }
    }
}
