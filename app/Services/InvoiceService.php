<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InvoiceService
{
    public function generateNumber(User $user, ?Client $client = null, ?int $year = null): string
    {
        $year ??= now()->year;

        return $this->nextNumber(
            $this->numberPrefix($client, $year),
            $this->existingNumbers($user),
            $this->clientPrefixes($user, $year),
        );
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, string> client id => suggested invoice number
     */
    public function generateNumbersForClients(User $user, Collection $clients, ?int $year = null): array
    {
        $year ??= now()->year;
        $numbers = $this->existingNumbers($user);
        $prefixes = $this->clientPrefixes($user, $year);

        return $clients
            ->mapWithKeys(fn (Client $client) => [
                $client->id => $this->nextNumber($this->numberPrefix($client, $year), $numbers, $prefixes),
            ])
            ->all();
    }

    /**
     * Číslo faktury se skládá z předpony klienta, roku, přípony klienta a pořadového čísla.
     */
    public function numberPrefix(?Client $client, ?int $year = null): string
    {
        $year ??= now()->year;

        return ($client?->invoice_number_prefix ?? '').$year.($client?->invoice_number_suffix ?? '');
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

    /**
     * @param  Collection<int, string>  $numbers
     * @param  array<int, string>  $allPrefixes
     */
    private function nextNumber(string $prefix, Collection $numbers, array $allPrefixes): string
    {
        // Předpona bez přípony je začátkem předpon s příponou ("2026" vs. "2026A"),
        // takže cizí řady je potřeba z hledání pořadového čísla vyloučit.
        $longerPrefixes = array_filter(
            $allPrefixes,
            fn (string $other) => strlen($other) > strlen($prefix) && str_starts_with($other, $prefix),
        );

        $maxSequence = $numbers
            ->filter(fn (string $number) => str_starts_with($number, $prefix))
            ->reject(fn (string $number) => (bool) array_filter(
                $longerPrefixes,
                fn (string $other) => str_starts_with($number, $other),
            ))
            ->map(fn (string $number) => (int) preg_replace('/\D/', '', substr($number, strlen($prefix))))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($maxSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, string>
     */
    private function existingNumbers(User $user): Collection
    {
        return Invoice::query()
            ->where('user_id', $user->id)
            ->pluck('number');
    }

    /**
     * @return array<int, string>
     */
    private function clientPrefixes(User $user, int $year): array
    {
        return Client::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNotNull('invoice_number_prefix')
                    ->orWhereNotNull('invoice_number_suffix');
            })
            ->get(['invoice_number_prefix', 'invoice_number_suffix'])
            ->map(fn (Client $client) => $this->numberPrefix($client, $year))
            ->unique()
            ->values()
            ->all();
    }
}
