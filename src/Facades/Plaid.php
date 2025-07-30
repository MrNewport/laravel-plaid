<?php

namespace MrNewport\LaravelPlaid\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \MrNewport\LaravelPlaid\Services\AccountsService accounts()
 * @method static \MrNewport\LaravelPlaid\Services\AssetsService assets()
 * @method static \MrNewport\LaravelPlaid\Services\AuthService auth()
 * @method static \MrNewport\LaravelPlaid\Services\EmploymentService employment()
 * @method static \MrNewport\LaravelPlaid\Services\IdentityService identity()
 * @method static \MrNewport\LaravelPlaid\Services\IdentityVerificationService identityVerification()
 * @method static \MrNewport\LaravelPlaid\Services\IncomeService income()
 * @method static \MrNewport\LaravelPlaid\Services\InstitutionsService institutions()
 * @method static \MrNewport\LaravelPlaid\Services\InvestmentsService investments()
 * @method static \MrNewport\LaravelPlaid\Services\ItemService item()
 * @method static \MrNewport\LaravelPlaid\Services\LiabilitiesService liabilities()
 * @method static \MrNewport\LaravelPlaid\Services\LinkTokenService linkToken()
 * @method static \MrNewport\LaravelPlaid\Services\PaymentInitiationService paymentInitiation()
 * @method static \MrNewport\LaravelPlaid\Services\ProcessorService processor()
 * @method static \MrNewport\LaravelPlaid\Services\SandboxService sandbox()
 * @method static \MrNewport\LaravelPlaid\Services\StatementsService statements()
 * @method static \MrNewport\LaravelPlaid\Services\TransactionsService transactions()
 * @method static \MrNewport\LaravelPlaid\Services\TransferService transfer()
 * @method static \MrNewport\LaravelPlaid\PlaidClient getClient()
 *
 * @see \MrNewport\LaravelPlaid\Plaid
 */
class Plaid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MrNewport\LaravelPlaid\Plaid::class;
    }
}