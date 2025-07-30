<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\DTOs\Account;
use MrNewport\LaravelPlaid\DTOs\AccountBalance;

class AccountsService extends BaseService
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
        
        $response = $this->client->post('/accounts/get', $data);
        
        return [
            'accounts' => array_map(fn($account) => new Account($account), $response['accounts']),
            'item' => $response['item'],
            'request_id' => $response['request_id'],
        ];
    }

    public function getBalance(string $accessToken, ?array $accountIds = null, array $options = []): array
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
        
        $response = $this->client->post('/accounts/balance/get', $data);
        
        return [
            'accounts' => array_map(fn($account) => new AccountBalance($account), $response['accounts']),
            'item' => $response['item'],
            'request_id' => $response['request_id'],
        ];
    }
}