<?php

namespace App\Support;

use Illuminate\Support\Str;

class Spayd
{
    /** @var array<int, string> */
    private array $parts = ['SPD*1.0'];

    public static function make(): self
    {
        return new self();
    }

    public function account(string $iban): self
    {
        $this->parts[] = 'ACC:'.strtoupper(str_replace(' ', '', $iban));

        return $this;
    }

    public function amount(float $amount): self
    {
        $this->parts[] = 'AM:'.number_format($amount, 2, '.', '');

        return $this;
    }

    public function currency(string $currency = 'CZK'): self
    {
        $this->parts[] = 'CC:'.strtoupper($currency);

        return $this;
    }

    public function variableSymbol(?string $variableSymbol): self
    {
        $vs = preg_replace('/\D/', '', $variableSymbol ?? '') ?? '';

        if ($vs !== '') {
            $this->parts[] = 'X-VS:'.substr($vs, 0, 10);
        }

        return $this;
    }

    public function dueDate(?string $yyyymmdd): self
    {
        if ($yyyymmdd !== null && preg_match('/^\d{8}$/', $yyyymmdd)) {
            $this->parts[] = 'DT:'.$yyyymmdd;
        }

        return $this;
    }

    public function message(?string $message): self
    {
        $sanitized = self::sanitizeText($message);

        if ($sanitized !== '') {
            $this->parts[] = 'MSG:'.Str::limit($sanitized, 60, '');
        }

        return $this;
    }

    public function recipientName(?string $name): self
    {
        $sanitized = self::sanitizeText($name);

        if ($sanitized !== '') {
            $this->parts[] = 'RN:'.Str::limit($sanitized, 35, '');
        }

        return $this;
    }

    public function toString(): string
    {
        return implode('*', $this->parts);
    }

    public static function sanitizeText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = Str::ascii($value);
        $value = strtoupper($value);
        $value = preg_replace('#[^A-Z0-9 $%+\-./:]#', '', $value) ?? '';

        return trim($value);
    }
}
