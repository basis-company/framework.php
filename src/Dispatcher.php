<?php

namespace Basis;

use Exception;

class Dispatcher
{
    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service === null) {
            $service = explode('.', $job)[0];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'content-type: application/x-www-form-urlencoded',
                'x-real-ip: '.$_SERVER['HTTP_X_REAL_IP'],
                'x-session: '.$_SERVER['HTTP_X_SESSION'],
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'rpc' => json_encode([
                    'job'    => $job,
                    'params' => $params,
                ])
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => "http://$service/api",
        ]);

        $contents = curl_exec($curl);
        curl_close($curl);

        if (!$contents) {
            throw new Exception("Host $service unreachable");
        }

        $result = json_decode($contents);
        if (!$result || !$result->success) {
            throw new Exception($result->message ?: $contents);
        }

        return $result->data;
    }
}
