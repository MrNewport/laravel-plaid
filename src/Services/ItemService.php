<?php

namespace MrNewport\LaravelPlaid\Services;

class ItemService extends BaseService
{
    public function get(string $accessToken): array
    {
        return $this->client->post('/item/get', [
            'access_token' => $accessToken,
        ]);
    }

    public function remove(string $accessToken): array
    {
        return $this->client->post('/item/remove', [
            'access_token' => $accessToken,
        ]);
    }

    public function publicTokenExchange(string $publicToken): array
    {
        $response = $this->client->post('/item/public_token/exchange', [
            'public_token' => $publicToken,
        ]);
        
        return [
            'access_token' => $response['access_token'],
            'item_id' => $response['item_id'],
            'request_id' => $response['request_id'],
        ];
    }

    public function createPublicToken(string $accessToken): array
    {
        $response = $this->client->post('/item/public_token/create', [
            'access_token' => $accessToken,
        ]);
        
        return [
            'public_token' => $response['public_token'],
            'request_id' => $response['request_id'],
        ];
    }

    public function accessTokenInvalidate(string $accessToken): array
    {
        return $this->client->post('/item/access_token/invalidate', [
            'access_token' => $accessToken,
        ]);
    }

    public function webhook(string $accessToken, string $webhookUrl): array
    {
        return $this->client->post('/item/webhook/update', [
            'access_token' => $accessToken,
            'webhook' => $webhookUrl,
        ]);
    }

    public function import(array $products, array $userAuth): array
    {
        return $this->client->post('/item/import', [
            'products' => $products,
            'user_auth' => $userAuth,
        ]);
    }
}