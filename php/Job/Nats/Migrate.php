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
        $api = $client->getApi();
        foreach ($dispatcher->dispatch('nats.streams')->streams as $info) {
            $stream = $api->getStream($info->name);
            if (!$stream->exists()) {
                $stream->getConfiguration()
                       ->setDiscardPolicy(DiscardPolicy::NEW)
                       ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
                       ->setStorageBackend(StorageBackend::FILE)
                       ->setSubjects([$info->name])
                       ->setMaxConsumers(1);

                $stream->create();
            }

            $consumer = $stream->getConsumer($info->name);
            if (!$consumer->exists()) {
                $consumer->getConfiguration()->setSubjectFilter($info->name);
                $consumer->create();
            }
        }
    }
}
