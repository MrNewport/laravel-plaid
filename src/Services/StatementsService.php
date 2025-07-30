<?php

namespace MrNewport\LaravelPlaid\Services;

class StatementsService extends BaseService
{
    public function list(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/statements/list', $data);
    }

    public function download(string $accessToken, string $statementId): array
    {
        return $this->client->post('/statements/download', [
            'access_token' => $accessToken,
            'statement_id' => $statementId,
        ]);
    }

    public function refresh(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/statements/refresh', $data);
    }
}