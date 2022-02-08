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
                    ->setAllowRollupHeaders(true)
                   ->setDenyDelete(false)
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
                // i'm not sure we really want to control how many parallel executions are performed
                // $consumer->getConfiguration()
                //     ->setMaxAckPending($handler['threads']);
            }

            if (!$consumer->exists()) {
                $consumer->create();
            }

            if (array_key_exists('job', $handler)) {
                if (!array_key_exists($handler['job'], $jobs)) {
                    $jobs[$handler['job']] = [];
                }
                $jobs[$handler['job']][] = $handler;
            }
        }

        $bucket = $client->getApi()->getBucket('service_handlers');
        foreach ($jobs as $job => $handler) {
            $bucket->put('job_' . str_replace('.', '_', $job), json_encode($handler));
        }
    }
}
