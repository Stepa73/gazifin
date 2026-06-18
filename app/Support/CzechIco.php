<?php

namespace App\Support;

class CzechIco
{
    public static function normalize(string $ico): string
    {
        return str_pad(preg_replace('/\D/', '', $ico) ?? '', 8, '0', STR_PAD_LEFT);
    }

    public static function isValid(string $ico): bool
    {
        $ico = self::normalize($ico);

        if (! preg_match('/^\d{8}$/', $ico)) {
            return false;
        }

        $weights = [8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $ico[$i] * $weights[$i];
        }

        $remainder = $sum % 11;
        $check = match ($remainder) {
            0 => 1,
            1 => 0,
            default => 11 - $remainder,
        };

        if ($check === 10) {
            return false;
        }

        return (int) $ico[7] === $check;
    }
}
