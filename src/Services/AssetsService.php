<?php

namespace MrNewport\LaravelPlaid\Services;

class AssetsService extends BaseService
{
    public function reportCreate(array $tokens, int $daysRequested, array $options = []): array
    {
        $data = [
            'access_tokens' => $tokens,
            'days_requested' => $daysRequested,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/asset_report/create', $data);
    }

    public function reportGet(string $assetReportToken, bool $includeInsights = false): array
    {
        $data = [
            'asset_report_token' => $assetReportToken,
        ];
        
        if ($includeInsights) {
            $data['include_insights'] = true;
        }
        
        return $this->client->post('/asset_report/get', $data);
    }

    public function reportPdfGet(string $assetReportToken): string
    {
        $response = $this->client->post('/asset_report/pdf/get', [
            'asset_report_token' => $assetReportToken,
        ]);
        
        return base64_decode($response['pdf']);
    }

    public function reportRemove(string $assetReportToken): array
    {
        return $this->client->post('/asset_report/remove', [
            'asset_report_token' => $assetReportToken,
        ]);
    }

    public function reportAuditCopyCreate(string $assetReportToken, string $auditorId): array
    {
        return $this->client->post('/asset_report/audit_copy/create', [
            'asset_report_token' => $assetReportToken,
            'auditor_id' => $auditorId,
        ]);
    }

    public function reportAuditCopyGet(string $auditCopyToken): array
    {
        return $this->client->post('/asset_report/audit_copy/get', [
            'audit_copy_token' => $auditCopyToken,
        ]);
    }

    public function reportAuditCopyRemove(string $auditCopyToken): array
    {
        return $this->client->post('/asset_report/audit_copy/remove', [
            'audit_copy_token' => $auditCopyToken,
        ]);
    }

    public function reportRefresh(string $assetReportToken, int $daysRequested, array $options = []): array
    {
        $data = [
            'asset_report_token' => $assetReportToken,
            'days_requested' => $daysRequested,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/asset_report/refresh', $data);
    }

    public function reportFilter(string $assetReportToken, array $accountIdsToInclude): array
    {
        return $this->client->post('/asset_report/filter', [
            'asset_report_token' => $assetReportToken,
            'account_ids_to_include' => $accountIdsToInclude,
        ]);
    }
}