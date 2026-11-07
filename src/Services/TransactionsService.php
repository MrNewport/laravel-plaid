<?php

namespace MrNewport\LaravelPlaid\Services;

use MrNewport\LaravelPlaid\DTOs\Transaction;
use MrNewport\LaravelPlaid\DTOs\ExactTransaction;
use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\Support\ExactJsonNumbers;

class TransactionsService extends BaseService
{
    public function sync(string $accessToken, ?string $cursor = null, int $count = 100, array $options = []): array
    {
        $response = $this->client->post('/transactions/sync', $this->syncRequest($accessToken, $cursor, $count, $options));

        return [
            'added' => array_map(fn($transaction) => new Transaction($transaction), $response['added']),
            'modified' => array_map(fn($transaction) => new Transaction($transaction), $response['modified']),
            'removed' => $response['removed'],
            'next_cursor' => $response['next_cursor'],
            'has_more' => $response['has_more'],
            'request_id' => $response['request_id'],
        ];
    }

    /** Exact source facts for read-only financial imports; existing sync() is unchanged. */
    public function syncExact(string $accessToken, ?string $cursor = null, int $count = 100,
        array $options = [], ?string $accountId = null): array
    {
        if ($accessToken === '' || $count < 1 || $count > 500) {
            throw new \InvalidArgumentException('Choose a bounded Plaid transaction request.');
        }
        if (array_key_exists('account_id', $options)) {
            throw new \InvalidArgumentException('Pass the Plaid account filter as a top-level argument.');
        }
        if ($accountId !== null && (strlen($accountId) < 1 || strlen($accountId) > 255
            || preg_match('/[\x00-\x20\x7f]/', $accountId))) {
            throw new \InvalidArgumentException('Choose a valid Plaid account identifier.');
        }
        $request = $this->syncRequest($accessToken, $cursor, $count, $options);
        if ($accountId !== null) {
            // Plaid keeps a distinct cursor per account when this top-level field is set.
            $request['account_id'] = $accountId;
        }
        $body = $this->client->postRaw('/transactions/sync', $request);
        $parsed = ExactJsonNumbers::decode($body);
        $response = $parsed['data'];
        foreach (['added', 'modified', 'removed'] as $key) {
            if (! is_array($response[$key] ?? null) || ! array_is_list($response[$key])
                || count($response[$key]) > 500) {
                throw new PlaidRequestException('Plaid returned an unsupported transaction page.');
            }
        }
        if (! is_bool($response['has_more'] ?? null)
            || ! is_string($response['next_cursor'] ?? null)
            || strlen($response['next_cursor']) > 4096
            || ! is_string($response['request_id'] ?? null)
            || strlen($response['request_id']) > 255) {
            throw new PlaidRequestException('Plaid returned an unsupported transaction cursor.');
        }
        $removed = [];
        foreach ($response['removed'] as $row) {
            if (! is_array($row)) {
                throw new PlaidRequestException('Plaid returned an unsupported removed transaction.');
            }
            $removed[] = [
                'transaction_id' => ExactTransaction::identifier($row['transaction_id'] ?? null),
                'account_id' => ExactTransaction::identifier($row['account_id'] ?? null),
            ];
        }

        return [
            'added' => array_map(fn ($row) => new ExactTransaction($row, $parsed['marker']), $response['added']),
            'modified' => array_map(fn ($row) => new ExactTransaction($row, $parsed['marker']), $response['modified']),
            'removed' => $removed,
            'next_cursor' => $response['next_cursor'],
            'has_more' => $response['has_more'],
            'request_id' => $response['request_id'],
            'response_sha256' => hash('sha256', $body),
        ];
    }

    private function syncRequest(string $accessToken, ?string $cursor, int $count, array $options): array
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
        
        return $data;
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
