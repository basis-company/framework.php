<?php

namespace Basis;

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class Storage
{
    use Toolkit;

    public string $hostname = 'storage';

    public function download(string $hash, int $retryCount = 10)
    {
        $result = @file_get_contents($this->url($hash));
        if ($result === false && $retryCount) {
            return $this->download($hash, $retryCount - 1);
        }
        return $result;
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

        if (property_exists($result, 'data') && $result->data) {
            return $result->data;
        }

        if (property_exists($result, 'hash') && is_array($result->hash)) {
            return $result->hash[0];
        }
    }

    public function url(string $hash): string
    {
        return "http://$this->hostname/storage/get?$hash";
    }
}
