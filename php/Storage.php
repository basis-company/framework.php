<?php

namespace Basis;

use GuzzleHttp\Client;

class Storage
{
    use Toolkit;

    public string $hostname = 'storage';

    public function download(string $hash)
    {
        return file_get_contents($this->url($hash));
    }

    public function upload(string $filename, $contents): string
    {
        if ($this->getContainer()->has(Client::class)) {
            $client = $this->getContainer()->get(Client::class);
        } else {
            $client = $this->getContainer()->create(Client::class);
        }

        $response = $client->request('POST', "http://$this->hostname/storage/upload", [
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => $filename,
                    'contents' => $contents,
                ],
            ]
        ]);

        return json_decode($response->getBody())->hash[0];
    }

    public function url(string $hash): string
    {
        return "http://$this->hostname/storage/get?$hash";
    }
}
