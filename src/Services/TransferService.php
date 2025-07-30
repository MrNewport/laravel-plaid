<?php

namespace MrNewport\LaravelPlaid\Services;

class TransferService extends BaseService
{
    public function create(array $data): array
    {
        return $this->client->post('/transfer/create', $data);
    }

    public function get(string $transferId): array
    {
        return $this->client->post('/transfer/get', [
            'transfer_id' => $transferId,
        ]);
    }

    public function list(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $count = null,
        ?int $offset = null,
        ?string $originationAccountId = null
    ): array {
        $data = [];
        
        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
        }
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        
        if ($originationAccountId !== null) {
            $data['origination_account_id'] = $originationAccountId;
        }
        
        return $this->client->post('/transfer/list', $data);
    }

    public function cancel(string $transferId): array
    {
        return $this->client->post('/transfer/cancel', [
            'transfer_id' => $transferId,
        ]);
    }

    public function eventList(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $transferId = null,
        ?string $accountId = null,
        ?string $transferType = null,
        ?array $eventTypes = null,
        ?string $sweepId = null,
        ?int $count = null,
        ?int $offset = null,
        ?string $originationAccountId = null
    ): array {
        $data = [];
        
        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
        }
        
        if ($transferId !== null) {
            $data['transfer_id'] = $transferId;
        }
        
        if ($accountId !== null) {
            $data['account_id'] = $accountId;
        }
        
        if ($transferType !== null) {
            $data['transfer_type'] = $transferType;
        }
        
        if ($eventTypes !== null) {
            $data['event_types'] = $eventTypes;
        }
        
        if ($sweepId !== null) {
            $data['sweep_id'] = $sweepId;
        }
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        
        if ($originationAccountId !== null) {
            $data['origination_account_id'] = $originationAccountId;
        }
        
        return $this->client->post('/transfer/event/list', $data);
    }

    public function eventSync(string $afterId, ?int $count = null): array
    {
        $data = [
            'after_id' => $afterId,
        ];
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        return $this->client->post('/transfer/event/sync', $data);
    }

    public function sweepGet(string $sweepId): array
    {
        return $this->client->post('/transfer/sweep/get', [
            'sweep_id' => $sweepId,
        ]);
    }

    public function sweepList(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $count = null,
        ?int $offset = null,
        ?string $originationAccountId = null
    ): array {
        $data = [];
        
        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
        }
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        
        if ($originationAccountId !== null) {
            $data['origination_account_id'] = $originationAccountId;
        }
        
        return $this->client->post('/transfer/sweep/list', $data);
    }

    public function authorizationCreate(array $data): array
    {
        return $this->client->post('/transfer/authorization/create', $data);
    }

    public function originationAccountUpdate(string $originationAccountId, array $data): array
    {
        $data['origination_account_id'] = $originationAccountId;
        
        return $this->client->post('/transfer/origination_account/update', $data);
    }

    public function originationAccountGet(string $originationAccountId): array
    {
        return $this->client->post('/transfer/origination_account/get', [
            'origination_account_id' => $originationAccountId,
        ]);
    }

    public function originationAccountList(
        ?int $count = null,
        ?int $offset = null
    ): array {
        $data = [];
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        
        return $this->client->post('/transfer/origination_account/list', $data);
    }

    public function repaymentGet(string $repaymentId): array
    {
        return $this->client->post('/transfer/repayment/get', [
            'repayment_id' => $repaymentId,
        ]);
    }

    public function repaymentList(
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $count = null,
        ?int $offset = null
    ): array {
        $data = [];
        
        if ($startDate !== null) {
            $data['start_date'] = $startDate;
        }
        
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
        }
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        
        return $this->client->post('/transfer/repayment/list', $data);
    }

    public function repaymentReturnList(string $repaymentId): array
    {
        return $this->client->post('/transfer/repayment/return/list', [
            'repayment_id' => $repaymentId,
        ]);
    }

    public function intentCreate(array $data): array
    {
        return $this->client->post('/transfer/intent/create', $data);
    }

    public function intentGet(string $transferIntentId): array
    {
        return $this->client->post('/transfer/intent/get', [
            'transfer_intent_id' => $transferIntentId,
        ]);
    }
}