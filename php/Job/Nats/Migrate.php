<?php

namespace Basis\Job\Nats;

use Basis\Dispatcher;
use Basis\Nats\Client;
use Basis\Nats\Stream\DiscardPolicy;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\StorageBackend;

class Migrate
{
    public bool $dropConsumer = false;
    public bool $dropStream = false;
    public bool $drop = false;

    public function run(Dispatcher $dispatcher, Client $client)
    {
        if ($this->drop) {
            $this->dropConsumer = true;
            $this->dropStream = true;
        }

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

            if (array_key_exists('configuration', $handler)) {
                if (array_key_exists('storage_backend', $handler['configuration'])) {
                    $stream->getConfiguration()
                        ->setStorageBackend($handler['configuration']['storage_backend']);
                }
                if (array_key_exists('duplicate_window', $handler['configuration'])) {
                    $stream->getConfiguration()
                        ->setDuplicateWindow($handler['configuration']['duplicate_window']);
                }
            }

            if ($this->dropStream && $stream->exists()) {
                $stream->delete();
            }

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

            if ($this->dropConsumer && $consumer->exists()) {
                $consumer->delete();
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
