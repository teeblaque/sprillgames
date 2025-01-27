<?php

namespace App\Services\SprillGamesService;

use GuzzleHttp\Client;

class SiruService
{

    public function siruAuth()
    {
        $params = [
            'client_id' => config('app.siru_client_id'),
            'client_secret' => config('app.siru_secret_key'),
        ];

        $response = (new Client())->request('POST', 'https://api-staging.sirumobile.com/auth', [
            'body' => json_encode($params),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody());
    }

    public function siruGet($url)
    {
        $auth = $this->siruAuth();
        $response = (new Client())->request(
            'GET',
            config('app.siru_base_url') . $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth->token,
                ],
            ]
        );
        return json_decode($response->getBody());
    }

    public function SiruPost($url, $params)
    {
        $auth = $this->siruAuth();
        $body = json_encode($params);
        $response = (new Client())->request('POST', config('app.siru_base_url') . $url, [
            'body' => $body,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $auth->token,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody());
    }
}
