<?php

namespace Basis;

use Swoole\Coroutine\Http\Client;

class Storage
{
    use Toolkit;

    public string $hostname = 'storage';

    public function download(string $hash)
    {
        return @file_get_contents($this->url($hash));
    }

    public function upload(string $filename, $contents): string
    {
        $container = $this->getContainer();
        if ($container->has(Client::class)) {
            $client = $container->get(Client::class);
        } else {
            $client = new Client($this->hostname, 80);
        }

        $extension = array_reverse(explode('.', $filename))[0];
        $tmp = '/tmp/' . md5($contents) . '.' . $extension;
        file_put_contents($tmp, $contents);

        $mime = mime_content_type($tmp);

        $client->addFile($tmp, $filename, $mime);
        $client->set(['timeout' => -1]);
        $client->post('/storage/upload', $contents);

        if (!$client->body) {
            throw new Exception("Host $this->hostname is unreachable");
        }

        $response = json_decode($client->body);
        $client->close();

        if ($response->data) {
            return $response->data;
        }


        return $response->hash[0];
    }

    public function url(string $hash): string
    {
        return "http://$this->hostname/storage/get?$hash";
    }
}
