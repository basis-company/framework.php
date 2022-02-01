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
        $subjects = [];

        $handlers = $dispatcher->getHandlers();
        foreach ($handlers as $handler) {
            $subjects[] = $handler['subject'];
        }

        $stream = $client->getApi()->getStream($dispatcher->getServiceName());
        $stream->getConfiguration()
               ->setDiscardPolicy(DiscardPolicy::NEW)
               ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
               ->setStorageBackend(StorageBackend::FILE)
               ->setSubjects($subjects);

        if (!$stream->exists()) {
            $stream->create();
        } else {
            $stream->update();
        }

        foreach ($handlers as $handler) {
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
        }

        $bucket = $client->getApi()->getBucket('service_handlers');
        $bucket->put('stream_' . $stream->getName(), json_encode($handlers));

        $jobs = [];
        foreach ($handlers as $config) {
            $bucket->put('subject_' . $config['subject'], json_encode($config));
            if (array_key_exists('job', $config)) {
                if (!array_key_exists($config['job'], $jobs)) {
                    $jobs[$config['job']] = [];
                }
                $jobs[$config['job']][] = $config;
            }
        }


        foreach ($jobs as $job => $config) {
            $bucket->put('job_' . str_replace('.', '_', $job), json_encode($config));
        }
    }
}
