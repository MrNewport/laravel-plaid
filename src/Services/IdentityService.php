<?php

namespace MrNewport\LaravelPlaid\Services;

class IdentityService extends BaseService
{
    public function get(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/identity/get', $data);
    }

    public function match(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/identity/match', $data);
    }
}