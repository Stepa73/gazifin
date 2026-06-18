<?php

namespace App\Support;

use InvalidArgumentException;

class CzechBankAccount
{
    public static function toIban(string $bankAccount): string
    {
        $normalized = str_replace(' ', '', $bankAccount);

        if (! preg_match('/^(?:(\d{1,6})-)?(\d{1,10})\/(\d{4})$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Neplatný formát čísla účtu.');
        }

        $prefix = str_pad($matches[1] ?? '', 6, '0', STR_PAD_LEFT);
        $account = str_pad($matches[2], 10, '0', STR_PAD_LEFT);
        $bankCode = $matches[3];
        $bban = $bankCode.$prefix.$account;

        $checkString = $bban.'123500';
        $remainder = self::mod97($checkString);
        $checkDigits = str_pad((string) (98 - $remainder), 2, '0', STR_PAD_LEFT);

        $iban = 'CZ'.$checkDigits.$bban;

        if (! self::isValidIban($iban)) {
            throw new InvalidArgumentException('Neplatné číslo účtu (IBAN kontrolní součet).');
        }

        return $iban;
    }

    public static function isValidIban(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (! preg_match('/^CZ\d{22}$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';

        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        return self::mod97($numeric) === 1;
    }

    private static function mod97(string $numericString): int
    {
        $remainder = 0;

        foreach (str_split($numericString) as $char) {
            $remainder = ($remainder * 10 + (int) $char) % 97;
        }

        return $remainder;
    }
}
