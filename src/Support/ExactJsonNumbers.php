<?php

namespace MrNewport\LaravelPlaid\Support;

use MrNewport\LaravelPlaid\Exceptions\PlaidRequestException;

/** Keeps JSON number lexemes distinct from JSON strings before decoding. */
final class ExactJsonNumbers
{
    /** @return array{data:array,marker:string} */
    public static function decode(string $body): array
    {
        if ($body === '' || strlen($body) > 8388608) {
            throw new PlaidRequestException('Plaid returned an unsupported response size.');
        }
        $length = strlen($body);
        $cursor = 0;
        $copiedUntil = 0;
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
                if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/',
                    $body, $match, 0, $cursor) !== 1) {
                    throw new PlaidRequestException('Plaid returned invalid JSON.');
                }
                $number = $match[0];
                $quoted .= substr($body, $copiedUntil, $cursor - $copiedUntil)
                    .'{' . '"'.$marker.'":"'.$number.'"}';
                $cursor += strlen($number);
                $copiedUntil = $cursor;
                continue;
            }
            $cursor++;
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
