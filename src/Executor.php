<?php

namespace Basis;

use Basis\Procedure\JobQueue\Cast;
use Basis\Procedure\JobQueue\Take;
use Exception;

class Executor
{
    use Toolkit;

    public function request($request)
    {
        if (!array_key_exists('job', $request) || !$request['job']) {
            throw new Exception("No job defined");
        }

        if (!array_key_exists('service', $request) || !$request['service']) {
            if ($this->get(Runner::class)->hasJob($request['job'])) {
                $request['service'] = $this->getServiceName();
            } else {
                $request['service'] = explode('.', $request['job'])[0];
            }
        }

        if (!array_key_exists('params', $request)) {
            $request['params'] = [];
        } else {
            $request['params'] = $this->get(Converter::class)->toArray($request['params']);
        }

        $context = $this->get(Context::class)->toArray();
        $jobContext = $this->findOrCreate('job_context', [
            'hash' => md5(json_encode($context))
        ]);
        if (!$jobContext->context) {
            $jobContext->context = $context;
            $jobContext->save();
        }

        $request['context'] = $jobContext->id;

        $request['hash'] = md5(json_encode([
            $request['service'],
            $request['job'],
            $request['params'],
        ]));

        // ready
        $request['status'] = 'r';

        $tuple = [];
        foreach ($this->getRepository('job_queue')->getSpace()->getTupleMap() as $key => $index) {
            if (array_key_exists($key, $request)) {
                $tuple[$index] = $request[$key];
            }
        }

        $casting = $this->get(Cast::class)($request['hash'], $tuple);

        $this->getRepository('job_queue')->flushCache();
        return $this->getRepository('job_queue')->getInstance($casting['tuple']);
    }

    public function send(string $job, array $params = [], string $service = null)
    {
        $this->request(compact('job', 'params', 'service'));
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        $recipient = $this->getServiceName();
        $request = $this->request(compact('job', 'params', 'service', 'recipient'));

        return $this->result($request->hash);
    }

    public function process()
    {
        $tuple = $this->get(Take::class)();
        if (!$tuple) {
            return;
        }

        $request = $this->getRepository('job_queue')->getInstance($tuple);

        if ($request->service != $this->getServiceName()) {
            // move to service queue
            throw new Exception("Error Processing Request", 1);

        } else {
            $context = $this->get(Context::class);
            $runner = $this->get(Runner::class);

            $contextBackup = $context->toArray();
            $jobContext = $this->findOrFail('job_context', $request->context);
            $context->reset($jobContext->context);
            $result = $runner->dispatch($request->job, $request->params);

            $context->reset($contextBackup);

            if ($request->recipient) {
                $this->create('job_result', [
                    'service' => $request->recipient,
                    'hash' => $request->hash,
                    'data' => $this->get(Converter::class)->toArray($result),
                    'expire' => property_exists($result, 'expire') ? $result->expire : 0,
                ]);
            }
        }

        return $this->getMapper()->remove($request);
    }

    public function result($hash)
    {
        $result = $this->findOne('job_result', [
            'service' => $this->getServiceName(),
            'hash' => $hash,
        ]);

        if (!$result) {
            if (!$this->process()) {
                usleep(50000); // 50 milliseconds sleep
            }
            return $this->result($hash);
        }

        return $this->get(Converter::class)->toObject($result->data);
    }

    public function getServiceName()
    {
        return $this->get(Service::class)->getName();
    }
}
