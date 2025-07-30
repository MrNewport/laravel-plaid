<?php

namespace MrNewport\LaravelPlaid\Services;

class InvestmentsService extends BaseService
{
    public function holdingsGet(string $accessToken, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/investments/holdings/get', $data);
    }

    public function transactionsGet(
        string $accessToken,
        string $startDate,
        string $endDate,
        array $options = []
    ): array {
        $data = [
            'access_token' => $accessToken,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/investments/transactions/get', $data);
    }

    public function refresh(string $accessToken): array
    {
        return $this->client->post('/investments/refresh', [
            'access_token' => $accessToken,
        ]);
    }
}