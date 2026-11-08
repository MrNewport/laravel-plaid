<?php

namespace MrNewport\LaravelPlaid\Support;

use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;

/** Keeps JSON number lexemes distinct from JSON strings before decoding. */
final class ExactJsonNumbers
{
    private const MAX_RAW_BYTES = 8388608;
    private const MAX_REWRITTEN_BYTES = 9000000;
    private const MAX_NUMBER_TOKENS = 16384;
    private const MAX_STRUCTURAL_TOKENS = 131072;

    /** @return array{data:array,marker:string} */
    public static function decode(string $body): array
    {
        if ($body === '' || strlen($body) > self::MAX_RAW_BYTES) {
            throw new PlaidRequestException('Plaid returned an unsupported response size.');
        }
        $length = strlen($body);
        $cursor = 0;
        $copiedUntil = 0;
        $numbers = 0;
        $structure = 0;
        $quoted = '';
        $marker = '__plaid_exact_json_number_'.bin2hex(random_bytes(16));
        while ($cursor < $length) {
            $char = $body[$cursor];
            if ($char === '"') {
                $cursor++;
                while ($cursor < $length) {
                    if ($body[$cursor] === '\\') {
                        $cursor += 2;
                    } elseif ($body[$cursor] === '"') {
                        $cursor++;
                        break;
                    } else {
                        $cursor++;
                    }
                }
                continue;
            }
            if ($char === '-' || ($char >= '0' && $char <= '9')) {
                if (++$numbers > self::MAX_NUMBER_TOKENS) {
                    throw new PlaidRequestException('Plaid response has too many numeric facts.');
                }
                if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/',
                    $body, $match, 0, $cursor) !== 1) {
                    throw new PlaidRequestException('Plaid returned invalid JSON.');
                }
                $number = $match[0];
                $literal = '{' . '"'.$marker.'":"'.$number.'"}';
                if (strlen($quoted) + ($cursor - $copiedUntil) + strlen($literal)
                    > self::MAX_REWRITTEN_BYTES) {
                    throw new PlaidRequestException('Plaid response expansion exceeds the supported bound.');
                }
                $quoted .= substr($body, $copiedUntil, $cursor - $copiedUntil).$literal;
                $cursor += strlen($number);
                $copiedUntil = $cursor;
                continue;
            }
            if (str_contains('{}[],:', $char)
                && ++$structure > self::MAX_STRUCTURAL_TOKENS) {
                throw new PlaidRequestException('Plaid response has too many JSON facts.');
            }
            $cursor++;
        }
        if (strlen($quoted) + ($length - $copiedUntil) > self::MAX_REWRITTEN_BYTES) {
            throw new PlaidRequestException('Plaid response expansion exceeds the supported bound.');
        }
        $quoted .= substr($body, $copiedUntil);
        try {
            $decoded = json_decode($quoted, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PlaidRequestException('Plaid returned invalid JSON.', 0, $exception);
        }
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new PlaidRequestException('Plaid returned an invalid response object.');
        }

        return ['data' => $decoded, 'marker' => $marker];
    }

    public static function decimal(mixed $value, string $marker): string
    {
        if (! is_array($value) || array_keys($value) !== [$marker]
            || ! is_string($value[$marker])
            || strlen($value[$marker]) > 48
            || preg_match('/\A-?(?:0|[1-9][0-9]{0,31})(?:\.[0-9]{1,12})?\z/D',
                $value[$marker]) !== 1) {
            throw new PlaidRequestException('Plaid returned an unsupported exact amount.');
        }

        return $value[$marker];
    }
}
