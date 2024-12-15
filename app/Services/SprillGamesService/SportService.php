<?php

namespace App\Services\SprillGamesService;

use GuzzleHttp\Client;

class SportService{
    
    public function getNext5Fixtures($date)
    {
        $response = (new Client())->request('GET', config('app.sprillgames_api_url').'/fixtures?date='.$date, [
            'headers' => [
                'Accept' => 'application/json',
                'x-rapidapi-key' => config('app.sprillgames_api_key'),
                'x-rapidapi-host' => 'api-football-v1.p.rapidapi.com',
            ],
        ]);

        return json_decode($response->getBody());
    }
}
