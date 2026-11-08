<?php

namespace MrNewport\LaravelPlaid\DTOs;

use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;
use MrNewport\LaravelPlaid\Support\ExactJsonNumbers;

/** Read-only provider fact. Money remains source decimal text, never a float. */
final class ExactTransaction
{
    public readonly string $transaction_id;
    public readonly string $account_id;
    public readonly string $amount_decimal;
    public readonly ?string $iso_currency_code;
    public readonly ?string $unofficial_currency_code;
    public readonly bool $pending;
    public readonly ?string $pending_transaction_id;
    public readonly string $date;
    public readonly ?string $name;

    public function __construct(array $data, string $numberMarker)
    {
        $this->transaction_id = self::identifier($data['transaction_id'] ?? null);
        $this->account_id = self::identifier($data['account_id'] ?? null);
        $this->amount_decimal = ExactJsonNumbers::decimal($data['amount'] ?? null, $numberMarker);
        $iso = $data['iso_currency_code'] ?? null;
        $unofficial = $data['unofficial_currency_code'] ?? null;
        if (($iso === null) === ($unofficial === null)
            || ($iso !== null && (! is_string($iso)
                || preg_match('/\A[A-Z]{3}\z/D', $iso) !== 1))
            || ($unofficial !== null && (! is_string($unofficial)
                || preg_match('/\A[A-Za-z0-9._-]{1,24}\z/D', $unofficial) !== 1))) {
            throw new PlaidRequestException('Plaid returned an unsupported currency.');
        }
        $this->iso_currency_code = $iso;
        $this->unofficial_currency_code = $unofficial;
        if (! is_bool($data['pending'] ?? null)) {
            throw new PlaidRequestException('Plaid returned an unsupported pending state.');
        }
        $this->pending = $data['pending'];
        $this->pending_transaction_id = isset($data['pending_transaction_id'])
            ? self::identifier($data['pending_transaction_id']) : null;
        if (! is_string($data['date'] ?? null)
            || preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/D', $data['date']) !== 1
            || ! checkdate((int) substr($data['date'], 5, 2),
                (int) substr($data['date'], 8, 2), (int) substr($data['date'], 0, 4))) {
            throw new PlaidRequestException('Plaid returned an unsupported transaction date.');
        }
        $this->date = $data['date'];
        $name = $data['name'] ?? null;
        if ($name !== null && (! is_string($name) || strlen($name) > 1024)) {
            throw new PlaidRequestException('Plaid returned an unsupported transaction name.');
        }
        $this->name = $name;
    }

    public static function identifier(mixed $value): string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 255
            || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new PlaidRequestException('Plaid returned an unsupported transaction identifier.');
        }

        return $value;
    }
}
