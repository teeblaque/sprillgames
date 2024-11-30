<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;


trait ConsumesExternalService
{
  // send a request to any service
  // @return string

  public function performRequest($method, $requestUrl, $formParams = [], $headers = [])
  {
    $client = new Client([
      //   'base_uri' => $this->baseUri,
    ]);

    try {
      $response = $client->request(
        $method,
        $requestUrl,
        [
          'form_params' => $formParams,
          'headers' => $headers
        ]
      );
    } catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return (json_decode($response->getBody(), true));
  }


  public function performTokenRequest($requestUrl, $formParams = [], $headers = [])
  {
    try {
      $response = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . config('app.sudo_api_key'), 'Cache-Control' => 'no-cache', 'accept' => 'application/json', 'content-type' => 'application/json'])
        ->withOptions(["verify" => false])
        ->post($requestUrl, $formParams);
    } catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return (json_decode($response->getBody(), true));
  }


  public function performGetTokenRequest($requestUrl, $formParams = [], $headers = [])
  {
    try {
      $response = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . config('app.sudo_api_key'), 'Cache-Control' => 'no-cache'])
        ->withOptions(["verify" => false])
        ->get($requestUrl);
    } catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return (json_decode($response->getBody(), true));
  }

  public function performPutTokenRequest($token, $requestUrl, $formParams = [], $headers = [])
  {
    try {
      $response = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . $token, 'Cache-Control' => 'no-cache'])
        ->withOptions(["verify" => false])
        ->put($requestUrl, $formParams);
    } catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return (json_decode($response->getBody(), true));
  }

  public function performPaystackRequest($requestUrl, $formParams = [], $headers = [])
  {
    try {
      $response = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . config('app.paystack_secret_key'), 'Cache-Control' => 'no-cache', 'accept' => 'application/json', 'content-type' => 'application/json'])
        ->withOptions(["verify" => false])
        ->post($requestUrl, $formParams);
    } catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return (json_decode($response->getBody(), true));
  }
}
