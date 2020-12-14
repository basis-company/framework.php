<?php

namespace Basis;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

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
        static $client;
        if (!$client) {
            $client = new CurlHttpClient();
        }

        $extension = array_reverse(explode('.', $filename))[0];
        $tmp = '/tmp/' . md5($contents) . '.' . $extension;
        file_put_contents($tmp, $contents);

        $formData = new FormDataPart([
            'file' => DataPart::fromPath($tmp),
        ]);

        $host = $this->dispatch('resolve.address', [ 'name' => 'storage' ])->host;
        $response = $client->request('POST', "http://$host/storage/upload", [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);
        $result = json_decode($response->getContent());

        return $result->data ? $result->data : $result->hash[0];
    }

    public function url(string $hash): string
    {
        return "http://$this->hostname/storage/get?$hash";
    }
}
