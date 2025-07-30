<?php

namespace MrNewport\LaravelPlaid\Services;

class ProcessorService extends BaseService
{
    public function tokenCreate(string $accessToken, string $accountId, string $processor): array
    {
        $response = $this->client->post('/processor/token/create', [
            'access_token' => $accessToken,
            'account_id' => $accountId,
            'processor' => $processor,
        ]);
        
        return [
            'processor_token' => $response['processor_token'],
            'request_id' => $response['request_id'],
        ];
    }

    public function stripeTokenCreate(string $accessToken, string $accountId): array
    {
        $response = $this->client->post('/processor/stripe/bank_account_token/create', [
            'access_token' => $accessToken,
            'account_id' => $accountId,
        ]);
        
        return [
            'stripe_bank_account_token' => $response['stripe_bank_account_token'],
            'request_id' => $response['request_id'],
        ];
    }

    public function apexTokenCreate(string $accessToken, string $accountId): array
    {
        return $this->tokenCreate($accessToken, $accountId, 'apex');
    }

    public function dwollaTokenCreate(string $accessToken, string $accountId): array
    {
        return $this->tokenCreate($accessToken, $accountId, 'dwolla');
    }

    public function authGet(string $processorToken): array
    {
        return $this->client->post('/processor/auth/get', [
            'processor_token' => $processorToken,
        ]);
    }

    public function identityGet(string $processorToken): array
    {
        return $this->client->post('/processor/identity/get', [
            'processor_token' => $processorToken,
        ]);
    }

    public function balanceGet(string $processorToken, array $options = []): array
    {
        $data = [
            'processor_token' => $processorToken,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/processor/balance/get', $data);
    }

    public function accountGet(string $processorToken): array
    {
        return $this->client->post('/processor/account/get', [
            'processor_token' => $processorToken,
        ]);
    }
}