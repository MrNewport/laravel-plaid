<?php

namespace MrNewport\LaravelPlaid\Services;

class SandboxService extends BaseService
{
    public function publicTokenCreate(
        string $institutionId,
        array $initialProducts,
        array $options = []
    ): array {
        $data = [
            'institution_id' => $institutionId,
            'initial_products' => $initialProducts,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/sandbox/public_token/create', $data);
    }

    public function itemFireWebhook(string $accessToken, string $webhookCode): array
    {
        return $this->client->post('/sandbox/item/fire_webhook', [
            'access_token' => $accessToken,
            'webhook_code' => $webhookCode,
        ]);
    }

    public function itemResetLogin(string $accessToken): array
    {
        return $this->client->post('/sandbox/item/reset_login', [
            'access_token' => $accessToken,
        ]);
    }

    public function itemSetVerificationStatus(
        string $accessToken,
        string $accountId,
        string $verificationStatus
    ): array {
        return $this->client->post('/sandbox/item/set_verification_status', [
            'access_token' => $accessToken,
            'account_id' => $accountId,
            'verification_status' => $verificationStatus,
        ]);
    }

    public function processorTokenCreate(string $institutionId, array $options = []): array
    {
        $data = [
            'institution_id' => $institutionId,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/sandbox/processor_token/create', $data);
    }

    public function bankTransferSimulate(
        string $bankTransferId,
        string $eventType,
        ?string $failureReason = null
    ): array {
        $data = [
            'bank_transfer_id' => $bankTransferId,
            'event_type' => $eventType,
        ];
        
        if ($failureReason !== null) {
            $data['failure_reason'] = $failureReason;
        }
        
        return $this->client->post('/sandbox/bank_transfer/simulate', $data);
    }

    public function transferSimulate(
        string $transferId,
        string $eventType,
        ?string $failureReason = null
    ): array {
        $data = [
            'transfer_id' => $transferId,
            'event_type' => $eventType,
        ];
        
        if ($failureReason !== null) {
            $data['failure_reason'] = $failureReason;
        }
        
        return $this->client->post('/sandbox/transfer/simulate', $data);
    }

    public function transferSweepSimulate(string $testClockId): array
    {
        return $this->client->post('/sandbox/transfer/sweep/simulate', [
            'test_clock_id' => $testClockId,
        ]);
    }

    public function transferRefundSimulate(string $refundId, string $testClockId): array
    {
        return $this->client->post('/sandbox/transfer/refund/simulate', [
            'refund_id' => $refundId,
            'test_clock_id' => $testClockId,
        ]);
    }

    public function incomeVerificationPrecheckUpdate(
        string $incomeVerificationPrecheckId,
        ?string $completionStatus = null,
        ?array $completionResult = null
    ): array {
        $data = [
            'income_verification_precheck_id' => $incomeVerificationPrecheckId,
        ];
        
        if ($completionStatus !== null) {
            $data['completion_status'] = $completionStatus;
        }
        
        if ($completionResult !== null) {
            $data['completion_result'] = $completionResult;
        }
        
        return $this->client->post('/sandbox/income/verification/precheck', $data);
    }
}