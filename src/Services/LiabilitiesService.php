<?php

namespace MrNewport\LaravelPlaid\Services;

class LiabilitiesService extends BaseService
{
    public function get(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/liabilities/get', $data);
    }
}