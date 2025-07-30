<?php

namespace MrNewport\LaravelPlaid;

use MrNewport\LaravelPlaid\Services\AccountsService;
use MrNewport\LaravelPlaid\Services\AssetsService;
use MrNewport\LaravelPlaid\Services\AuthService;
use MrNewport\LaravelPlaid\Services\EmploymentService;
use MrNewport\LaravelPlaid\Services\IdentityService;
use MrNewport\LaravelPlaid\Services\IdentityVerificationService;
use MrNewport\LaravelPlaid\Services\IncomeService;
use MrNewport\LaravelPlaid\Services\InstitutionsService;
use MrNewport\LaravelPlaid\Services\InvestmentsService;
use MrNewport\LaravelPlaid\Services\ItemService;
use MrNewport\LaravelPlaid\Services\LiabilitiesService;
use MrNewport\LaravelPlaid\Services\LinkTokenService;
use MrNewport\LaravelPlaid\Services\PaymentInitiationService;
use MrNewport\LaravelPlaid\Services\ProcessorService;
use MrNewport\LaravelPlaid\Services\SandboxService;
use MrNewport\LaravelPlaid\Services\StatementsService;
use MrNewport\LaravelPlaid\Services\TransactionsService;
use MrNewport\LaravelPlaid\Services\TransferService;

class Plaid
{
    protected PlaidClient $client;
    protected array $services = [];

    public function __construct(PlaidClient $client)
    {
        $this->client = $client;
    }

    public function accounts(): AccountsService
    {
        return $this->getService(AccountsService::class);
    }

    public function assets(): AssetsService
    {
        return $this->getService(AssetsService::class);
    }

    public function auth(): AuthService
    {
        return $this->getService(AuthService::class);
    }

    public function employment(): EmploymentService
    {
        return $this->getService(EmploymentService::class);
    }

    public function identity(): IdentityService
    {
        return $this->getService(IdentityService::class);
    }

    public function identityVerification(): IdentityVerificationService
    {
        return $this->getService(IdentityVerificationService::class);
    }

    public function income(): IncomeService
    {
        return $this->getService(IncomeService::class);
    }

    public function institutions(): InstitutionsService
    {
        return $this->getService(InstitutionsService::class);
    }

    public function investments(): InvestmentsService
    {
        return $this->getService(InvestmentsService::class);
    }

    public function item(): ItemService
    {
        return $this->getService(ItemService::class);
    }

    public function liabilities(): LiabilitiesService
    {
        return $this->getService(LiabilitiesService::class);
    }

    public function linkToken(): LinkTokenService
    {
        return $this->getService(LinkTokenService::class);
    }

    public function paymentInitiation(): PaymentInitiationService
    {
        return $this->getService(PaymentInitiationService::class);
    }

    public function processor(): ProcessorService
    {
        return $this->getService(ProcessorService::class);
    }

    public function sandbox(): SandboxService
    {
        return $this->getService(SandboxService::class);
    }

    public function statements(): StatementsService
    {
        return $this->getService(StatementsService::class);
    }

    public function transactions(): TransactionsService
    {
        return $this->getService(TransactionsService::class);
    }

    public function transfer(): TransferService
    {
        return $this->getService(TransferService::class);
    }

    protected function getService(string $serviceClass)
    {
        if (!isset($this->services[$serviceClass])) {
            $this->services[$serviceClass] = new $serviceClass($this->client);
        }

        return $this->services[$serviceClass];
    }

    public function getClient(): PlaidClient
    {
        return $this->client;
    }
}