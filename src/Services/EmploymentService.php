<?php

namespace MrNewport\LaravelPlaid\Services;

class EmploymentService extends BaseService
{
    public function verificationGet(string $accessToken): array
    {
        return $this->client->post('/employment/verification/get', [
            'access_token' => $accessToken,
        ]);
    }
}