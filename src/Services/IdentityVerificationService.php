<?php

namespace MrNewport\LaravelPlaid\Services;

class IdentityVerificationService extends BaseService
{
    public function create(array $data): array
    {
        return $this->client->post('/identity_verification/create', $data);
    }

    public function get(string $identityVerificationId): array
    {
        return $this->client->post('/identity_verification/get', [
            'identity_verification_id' => $identityVerificationId,
        ]);
    }

    public function list(
        string $clientUserId,
        ?string $cursor = null,
        ?int $count = null
    ): array {
        $data = [
            'client_user_id' => $clientUserId,
        ];
        
        if ($cursor !== null) {
            $data['cursor'] = $cursor;
        }
        
        if ($count !== null) {
            $data['count'] = $count;
        }
        
        return $this->client->post('/identity_verification/list', $data);
    }

    public function retryProcess(
        string $clientUserId,
        string $strategy,
        ?string $identityVerificationId = null,
        ?string $template = null,
        ?array $user = null
    ): array {
        $data = [
            'client_user_id' => $clientUserId,
            'strategy' => $strategy,
        ];
        
        if ($identityVerificationId !== null) {
            $data['identity_verification_id'] = $identityVerificationId;
        }
        
        if ($template !== null) {
            $data['template'] = $template;
        }
        
        if ($user !== null) {
            $data['user'] = $user;
        }
        
        return $this->client->post('/identity_verification/retry', $data);
    }
}