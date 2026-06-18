<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;

class InvoiceService
{
    public function generateNumber(User $user, ?int $year = null): string
    {
        $year ??= now()->year;
        $prefix = $year.'-';

        $maxSequence = Invoice::query()
            ->where('user_id', $user->id)
            ->where('number', 'like', $prefix.'%')
            ->pluck('number')
            ->map(fn (string $number) => (int) substr($number, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($maxSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    public function defaultDueDate(string|Carbon $issueDate): string
    {
        return Carbon::parse($issueDate)
            ->addMonthNoOverflow()
            ->day(15)
            ->format('Y-m-d');
    }

    public function defaultVariableSymbol(string $invoiceNumber): string
    {
        $digits = preg_replace('/\D/', '', $invoiceNumber) ?? '';

        return substr($digits, 0, 10);
    }

    /**
     * @param  array<int, array{description: string, quantity: float|string, unit_price: float|string}>  $items
     * @return array{subtotal: float, vat_rate: float, vat_amount: float, total: float}
     */
    public function calculateTotals(array $items, bool $isVatPayer): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $subtotal += round($quantity * $unitPrice, 2);
        }

        $vatRate = $isVatPayer ? 21.0 : 0.0;
        $vatAmount = $isVatPayer ? round($subtotal * ($vatRate / 100), 2) : 0.0;
        $total = round($subtotal + $vatAmount, 2);

        return [
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
        ];
    }
}
