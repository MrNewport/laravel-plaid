<?php

namespace MrNewport\LaravelPlaid\Services;

class IncomeService extends BaseService
{
    public function verificationCreate(string $clientUserId, string $webhook, array $options = []): array
    {
        $data = [
            'client_user_id' => $clientUserId,
            'webhook' => $webhook,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/income/verification/create', $data);
    }

    public function verificationDocumentsDownload(
        string $clientUserId,
        ?string $accessToken = null,
        ?string $incomeVerificationId = null,
        ?string $documentId = null
    ): array {
        $data = [
            'client_user_id' => $clientUserId,
        ];
        
        if ($accessToken !== null) {
            $data['access_token'] = $accessToken;
        }
        
        if ($incomeVerificationId !== null) {
            $data['income_verification_id'] = $incomeVerificationId;
        }
        
        if ($documentId !== null) {
            $data['document_id'] = $documentId;
        }
        
        return $this->client->post('/income/verification/documents/download', $data);
    }

    public function verificationPaystubsGet(
        ?string $incomeVerificationId = null,
        ?string $accessToken = null
    ): array {
        $data = [];
        
        if ($incomeVerificationId !== null) {
            $data['income_verification_id'] = $incomeVerificationId;
        }
        
        if ($accessToken !== null) {
            $data['access_token'] = $accessToken;
        }
        
        return $this->client->post('/income/verification/paystubs/get', $data);
    }

    public function verificationTaxformsGet(
        ?string $incomeVerificationId = null,
        ?string $accessToken = null
    ): array {
        $data = [];
        
        if ($incomeVerificationId !== null) {
            $data['income_verification_id'] = $incomeVerificationId;
        }
        
        if ($accessToken !== null) {
            $data['access_token'] = $accessToken;
        }
        
        return $this->client->post('/income/verification/taxforms/get', $data);
    }

    public function verificationSummaryGet(
        ?string $incomeVerificationId = null,
        ?string $accessToken = null
    ): array {
        $data = [];
        
        if ($incomeVerificationId !== null) {
            $data['income_verification_id'] = $incomeVerificationId;
        }
        
        if ($accessToken !== null) {
            $data['access_token'] = $accessToken;
        }
        
        return $this->client->post('/income/verification/summary/get', $data);
    }

    public function verificationRefresh(
        ?string $incomeVerificationId = null,
        ?string $accessToken = null
    ): array {
        $data = [];
        
        if ($incomeVerificationId !== null) {
            $data['income_verification_id'] = $incomeVerificationId;
        }
        
        if ($accessToken !== null) {
            $data['access_token'] = $accessToken;
        }
        
        return $this->client->post('/income/verification/refresh', $data);
    }
}