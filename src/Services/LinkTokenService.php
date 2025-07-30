<?php

namespace MrNewport\LaravelPlaid\Services;

class LinkTokenService extends BaseService
{
    public function create(array $data): array
    {
        $defaults = [
            'client_name' => config('app.name'),
            'language' => config('plaid.defaults.language', 'en'),
            'country_codes' => config('plaid.defaults.country_codes', ['US']),
        ];
        
        $data = array_merge($defaults, $data);
        
        $response = $this->client->post('/link/token/create', $data);
        
        return [
            'link_token' => $response['link_token'],
            'expiration' => $response['expiration'],
            'request_id' => $response['request_id'],
        ];
    }

    public function get(string $linkToken): array
    {
        return $this->client->post('/link/token/get', [
            'link_token' => $linkToken,
        ]);
    }
}