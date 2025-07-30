<?php

namespace MrNewport\LaravelPlaid\Services;

class PaymentInitiationService extends BaseService
{
    public function recipientCreate(array $data): array
    {
        return $this->client->post('/payment_initiation/recipient/create', $data);
    }

    public function recipientGet(string $recipientId): array
    {
        return $this->client->post('/payment_initiation/recipient/get', [
            'recipient_id' => $recipientId,
        ]);
    }

    public function recipientList(): array
    {
        return $this->client->post('/payment_initiation/recipient/list', []);
    }

    public function paymentCreate(array $data): array
    {
        return $this->client->post('/payment_initiation/payment/create', $data);
    }

    public function paymentGet(string $paymentId): array
    {
        return $this->client->post('/payment_initiation/payment/get', [
            'payment_id' => $paymentId,
        ]);
    }

    public function paymentList(array $options = []): array
    {
        return $this->client->post('/payment_initiation/payment/list', $options);
    }

    public function paymentReverse(string $paymentId, array $data = []): array
    {
        $data['payment_id'] = $paymentId;
        
        return $this->client->post('/payment_initiation/payment/reverse', $data);
    }

    public function consentCreate(array $data): array
    {
        return $this->client->post('/payment_initiation/consent/create', $data);
    }

    public function consentGet(string $consentId): array
    {
        return $this->client->post('/payment_initiation/consent/get', [
            'consent_id' => $consentId,
        ]);
    }

    public function consentRevoke(string $consentId): array
    {
        return $this->client->post('/payment_initiation/consent/revoke', [
            'consent_id' => $consentId,
        ]);
    }

    public function consentPaymentExecute(string $consentId, array $data): array
    {
        $data['consent_id'] = $consentId;
        
        return $this->client->post('/payment_initiation/consent/payment/execute', $data);
    }
}