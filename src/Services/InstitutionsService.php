<?php

namespace MrNewport\LaravelPlaid\Services;

class InstitutionsService extends BaseService
{
    public function get(string $institutionId, array $countryCodes, array $options = []): array
    {
        $data = [
            'institution_id' => $institutionId,
            'country_codes' => $countryCodes,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/institutions/get_by_id', $data);
    }

    public function search(
        string $query,
        array $countryCodes,
        ?array $products = null,
        array $options = []
    ): array {
        $data = [
            'query' => $query,
            'country_codes' => $countryCodes,
        ];
        
        if ($products !== null) {
            $data['products'] = $products;
        }
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/institutions/search', $data);
    }

    public function getList(
        array $countryCodes,
        int $count = 100,
        int $offset = 0,
        array $options = []
    ): array {
        $data = [
            'country_codes' => $countryCodes,
            'count' => $count,
            'offset' => $offset,
        ];
        
        if (!empty($options)) {
            $data['options'] = $options;
        }
        
        return $this->client->post('/institutions/get', $data);
    }
}