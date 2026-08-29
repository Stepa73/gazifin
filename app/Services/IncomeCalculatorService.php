<?php

namespace App\Services;

use App\Models\Client;
use App\Models\IncomePlan;
use App\Models\IncomeSource;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;

class IncomeCalculatorService
{
    /** Rok, pro který platí zadané sazby paušální daně. */
    public const BASE_YEAR = 2026;

    public const PREV_YEAR = self::BASE_YEAR - 1;

    public const NEXT_YEAR = self::BASE_YEAR + 1;

    /**
     * Plán uživatele. Při prvním otevření se založí a předvyplní podle evidovaných klientů.
     */
    public function planFor(User $user): IncomePlan
    {
        $plan = IncomePlan::firstOrCreate(['user_id' => $user->id]);

        if ($plan->sources()->exists()) {
            return $plan;
        }

        $clients = Client::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        if ($clients->isEmpty()) {
            $this->createSource($plan, null, 'Klient A', 0);

            return $plan;
        }

        foreach ($clients as $position => $client) {
            $this->createSource($plan, $client->id, $client->name, $position);
        }

        return $plan;
    }

    /**
     * Skutečné příjmy z faktur, po měsících.
     *
     * Měsíc určuje datum vystavení — to je měsíc, za který jsi si vydělal.
     * Splatnost by výsledek posunula, protože aplikace ji sama nastavuje na
     * 15. den následujícího měsíce, takže by červnová faktura spadla do července.
     *
     * Uvnitř měsíce se odděluje uhrazené od toho, co ještě čeká na zaplacení.
     *
     * @param  array<int, int>  $years
     * @return array{byClient: array<int, array<string, array{paid: float, open: float}>>, totals: array<string, array{paid: float, open: float}>}
     */
    public function actuals(User $user, array $years): array
    {
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->get(['client_id', 'issue_date', 'status', 'total']);

        $byClient = [];
        $totals = [];

        foreach ($invoices as $invoice) {
            $issued = $invoice->issue_date;

            if (! in_array((int) $issued->format('Y'), $years, true)) {
                continue;
            }

            $key = $issued->format('Y').'-'.((int) $issued->format('n') - 1);
            $bucket = $invoice->status === 'paid' ? 'paid' : 'open';
            $amount = (float) $invoice->total;

            $byClient[$invoice->client_id][$key] ??= ['paid' => 0.0, 'open' => 0.0];
            $byClient[$invoice->client_id][$key][$bucket] += $amount;

            $totals[$key] ??= ['paid' => 0.0, 'open' => 0.0];
            $totals[$key][$bucket] += $amount;
        }

        return ['byClient' => $byClient, 'totals' => $totals];
    }

    /**
     * Uloží celý stav kalkulačky. Zdroje se nahrazují, ne slučují — v prohlížeči
     * se dají mazat a přeskládávat, takže poslané pole je závazné.
     *
     * @param  array<string, mixed>  $state
     * @param  array<int, array<string, mixed>>  $sources
     */
    public function save(IncomePlan $plan, array $state, array $sources): void
    {
        $plan->update([
            'regime' => $state['regime'],
            'side_activity' => $state['sideActivity'],
            'activity' => $state['activity'],
            'exp_mode' => $state['expMode'],
            'exp_real' => $state['expReal'],
            'carry_amount' => $state['carryAmount'],
            'carry_month' => $state['carryMonth'],
        ]);

        $plan->sources()->delete();

        foreach ($sources as $position => $source) {
            $this->createSource(
                $plan,
                $source['clientId'] ?? null,
                $source['name'],
                $position,
                $source,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSource(IncomePlan $plan, ?int $clientId, string $name, int $position, array $attributes = []): IncomeSource
    {
        return $plan->sources()->create([
            'client_id' => $clientId,
            'name' => $name !== '' ? $name : 'Zdroj',
            'mode' => $attributes['mode'] ?? 'rate',
            'rate' => $attributes['rate'] ?? 1200,
            'unit' => $attributes['unit'] ?? 'h',
            'hours_per_day' => $attributes['hoursPerDay'] ?? 8,
            'payment_lag' => $attributes['lag'] ?? 2,
            'pay_day' => $attributes['payDay'] ?? 15,
            // Prázdné datum je platná volba (zdroj bez konce), takže výchozí hodnota
            // se použije jen tehdy, když klíč vůbec nedorazil.
            'starts_on' => array_key_exists('from', $attributes)
                ? $this->date($attributes['from'])
                : $this->date(self::BASE_YEAR.'-01-01'),
            'ends_on' => $this->date($attributes['to'] ?? null),
            'fixed_amount' => $attributes['fixed'] ?? 40000,
            'invoice_date' => array_key_exists('date', $attributes)
                ? $this->date($attributes['date'])
                : $this->date(self::BASE_YEAR.'-06-15'),
            'invoice_amount' => $attributes['amount'] ?? 0,
            'vacation' => $this->vacation($attributes['vacation'] ?? null),
            'position' => $position,
        ]);
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * @param  array<int, mixed>|null  $value
     * @return array<int, int>
     */
    private function vacation(?array $value): array
    {
        $days = array_fill(0, 12, 0);

        foreach (range(0, 11) as $month) {
            $days[$month] = max(0, min(31, (int) ($value[$month] ?? 0)));
        }

        return $days;
    }
}
