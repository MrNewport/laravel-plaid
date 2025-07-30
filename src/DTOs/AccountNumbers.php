<?php

namespace MrNewport\LaravelPlaid\DTOs;

class AccountNumbers extends BaseDTO
{
    public array $ach;
    public array $eft;
    public array $international;
    public array $bacs;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        
        $this->ach = $data['ach'] ?? [];
        $this->eft = $data['eft'] ?? [];
        $this->international = $data['international'] ?? [];
        $this->bacs = $data['bacs'] ?? [];
    }
}