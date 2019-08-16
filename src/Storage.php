<?php

namespace Basis;

use GuzzleHttp\Client;

class Storage
{
    public $hostname = 'storage';

    public function download(string $hash)
    {
        return file_get_contents($this->url($hash));
    }

    public function upload(string $filename, $contents) : string
    {
        $client = new Client();
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

    public function url(string $hash) : string
    {
        return "http://$this->hostname/storage/get?$hash";
    }
}
