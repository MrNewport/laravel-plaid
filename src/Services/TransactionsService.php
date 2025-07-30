<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\DTOs\Transaction;

class TransactionsService extends BaseService
{
    public function sync(string $accessToken, ?string $cursor = null, int $count = 100, array $options = []): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }
        
        if ($count !== 100) {
            $data['count'] = $count;
        }
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        $response = $this->client->post('/transactions/sync', $data);
        
        return [
            'added' => array_map(fn($transaction) => new Transaction($transaction), $response['added']),
            'modified' => array_map(fn($transaction) => new Transaction($transaction), $response['modified']),
            'removed' => $response['removed'],
            'next_cursor' => $response['next_cursor'],
            'has_more' => $response['has_more'],
            'request_id' => $response['request_id'],
        ];
    }

    public function get(
        string $accessToken,
        string $startDate,
        string $endDate,
        ?array $accountIds = null,
        int $count = 100,
        int $offset = 0,
        array $options = []
    ): array {
        $data = [
            'access_token' => $accessToken,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        
        if ($accountIds !== null) {
            $data['account_ids'] = $accountIds;
        }
        
        if ($count !== 100) {
            $data['count'] = $count;
        }
        
        if ($offset !== 0) {
            $data['offset'] = $offset;
        }
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        $response = $this->client->post('/transactions/get', $data);
        
        return [
            'accounts' => $response['accounts'],
            'transactions' => array_map(fn($transaction) => new Transaction($transaction), $response['transactions']),
            'item' => $response['item'],
            'total_transactions' => $response['total_transactions'],
            'request_id' => $response['request_id'],
        ];
    }

    public function refresh(string $accessToken): array
    {
        $response = $this->client->post('/transactions/refresh', [
            'access_token' => $accessToken,
        ]);
        
        return [
            'request_id' => $response['request_id'],
        ];
    }

    public function recurring(string $accessToken, ?array $accountIds = null): array
    {
        $data = [
            'access_token' => $accessToken,
        ];
        
        if ($accountIds !== null) {
            $data['account_ids'] = $accountIds;
        }
        
        $response = $this->client->post('/transactions/recurring/get', $data);
        
        return [
            'inflow_streams' => $response['inflow_streams'],
            'outflow_streams' => $response['outflow_streams'],
            'updated_datetime' => $response['updated_datetime'],
            'request_id' => $response['request_id'],
        ];
    }

    public function enrich(array $transactions, array $options = []): array
    {
        $data = [
            'transactions' => $transactions,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/transactions/enrich', $data);
    }

    public function categorize(string $query, ?array $options = null): array
    {
        $data = [
            'query' => $query,
        ];
        
        if ($options !== null) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/transactions/categorize', $data);
    }
}