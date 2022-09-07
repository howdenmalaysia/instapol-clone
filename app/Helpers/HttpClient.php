<?php

namespace App\Helpers;

use App\DataTransferObjects\Motor\ResponseData;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\HandlerStack;
use React\EventLoop\Factory;
use React\HttpClient\Client;
use React\Socket\Connector;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

class HttpClient
{
    public static function curl(string $method, string $url, array $options)
    {
        // Set http_errors to false
        $options['http_errors'] = false;

        $loop = Factory::create();
        $handler = new HttpClientAdapter($loop);
        $connector = new Connector($loop, [
            'dns' => $handler->getDnsResolver(),
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $react_client = new Client($loop, $connector);

        $handler->setHttpClient($react_client);

        $client = new GuzzleHttpClient([
            'handler' => HandlerStack::create($handler)
        ]);

        $status = false;
        $response = '';
        $response_header = [];

        $promise = $client->requestAsyns($method, $url, $options);
        $promise->then(
            function($res) use (&$status, &$response, &$response_header) {
                if($res->getStatusCode() === '200') {
                    $status = true;
                    $response = (string) $res->getBody();
                } else {
                    $response = json_decode($res->getBody());
                    $response->status_code = $res->getStatusCode();
                    $response = json_encode($response);
                }

                $response_header = $res->getHeaders();
            },
            function (Exception $ex) use (&$response) {
                $response = $ex->getMessage();
            }
        );

        $loop->run();

        return new ResponseData([
            'status' => $status,
            'response' => $response,
            'response_header' => $response_header
        ]);
    }
}