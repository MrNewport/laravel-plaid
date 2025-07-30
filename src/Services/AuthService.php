<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\DTOs\AccountNumbers;

class AuthService extends BaseService
{
    public function get(string $accessToken, ?array $accountIds = null, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if ($accountIds !== null) {
            $data['account_ids'] = $accountIds;
        }
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        $response = $this->client->post('/auth/get', $data);
        
        return [
            'accounts' => $response['accounts'],
            'numbers' => new AccountNumbers($response['numbers']),
            'item' => $response['item'],
            'request_id' => $response['request_id'],
        ];
    }
}