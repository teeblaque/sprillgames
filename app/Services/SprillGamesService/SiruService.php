<?php

namespace App\Services\SprillGamesService;

use GuzzleHttp\Client;

class SiruService
{

    public function siruAuth()
    {
        try {
            $params = [
                'client_id' => config('app.siru_client_id'),
                'client_secret' => config('app.siru_secret_key'),
            ];

            $response = (new Client())->request('POST', config('app.siru_base_url'), [
                'body' => json_encode($params),
            ]);
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        return (json_decode($response->getBody(), true));
    }

    public function siruGet($url)
    {
        try {
            $auth = $this->siruAuth();
            $response = (new Client())->request('GET', config('app.coralpay_base_url').$url,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $auth->token,
                    ],
                ]);
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        return (json_decode($response->getBody(), true));
    }

    public function SiruPost($url, $params)
    {
        try {
            $auth = $this->siruAuth();

            $body = json_encode($params, $url);
            $response = (new Client())->request('POST', config('app.siru_base_url') . $url, [
                'body' => $body,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $auth,
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        return (json_decode($response->getBody(), true));
    }
}
