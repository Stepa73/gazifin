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
    /** Rok, který kalkulačka bere jako „letošek“. */
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
     * Skutečnost z faktur, ve dvou pohledech.
     *
     * Plán sleduje, kdy peníze dorazí na účet. Porovnávat ho s fakturami podle
     * data vystavení znamená srovnávat dvě různé věci — proto se skutečnost
     * počítá primárně taky podle peněz:
     *
     *   received — uhrazené faktury v měsíci, kdy byly uhrazené,
     *   expected — zatím neuhrazené, v měsíci, kdy mají být splatné,
     *   issued   — všechno podle data vystavení, tedy za který měsíc sis vydělal.
     *
     * První dva dohromady dávají cash flow srovnatelný s plánem, třetí ukazuje
     * odvedenou práci. Rozdíl mezi nimi je přesně to zpoždění, které kalkulačka
     * modeluje zpožděním platby.
     *
     * @param  array<int, int>  $years
     * @return array{byClient: array<int, array<string, array<string, float>>>, totals: array<string, array<string, float>>}
     */
    public function actuals(User $user, array $years): array
    {
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->get(['client_id', 'issue_date', 'due_date', 'paid_at', 'status', 'total']);

        $byClient = [];
        $totals = [];

        $add = function (?int $clientId, ?Carbon $date, string $bucket, float $amount) use ($years, &$byClient, &$totals): void {
            if (! $date || ! in_array((int) $date->format('Y'), $years, true)) {
                return;
            }

            $key = $date->format('Y').'-'.((int) $date->format('n') - 1);
            $empty = ['received' => 0.0, 'expected' => 0.0, 'issued' => 0.0];

            $byClient[$clientId][$key] ??= $empty;
            $byClient[$clientId][$key][$bucket] += $amount;

            $totals[$key] ??= $empty;
            $totals[$key][$bucket] += $amount;
        };

        foreach ($invoices as $invoice) {
            $amount = (float) $invoice->total;

            $add($invoice->client_id, $invoice->issue_date, 'issued', $amount);

            if ($invoice->status === 'paid') {
                // Zaplacenou fakturu bez data úhrady neumíme umístit přesněji než na splatnost.
                $add($invoice->client_id, $invoice->paid_at ?? $invoice->due_date, 'received', $amount);
            } else {
                $add($invoice->client_id, $invoice->due_date, 'expected', $amount);
            }
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
            'pausal_months' => $state['pausalMonths'],
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
            'work_days' => $this->workDays($attributes['workDays'] ?? null),
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
            'vacation_days' => $this->vacationDays($attributes['vacationDays'] ?? null),
            'position' => $position,
        ]);
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Dny v týdnu, kdy se pro zdroj pracuje: 1 = pondělí … 7 = neděle.
     *
     * @param  array<int, mixed>|null  $value
     * @return array<int, int>
     */
    private function workDays(?array $value): array
    {
        if ($value === null) {
            return [1, 2, 3, 4, 5];
        }

        $days = collect($value)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $days ?: [1, 2, 3, 4, 5];
    }

    /**
     * Konkrétní dny dovolené. Dřív to byl počet dnů za měsíc, takže se tatáž
     * dovolená „vybírala“ znovu v každém roce a v kalendáři nebyla k nalezení.
     *
     * @param  array<int, mixed>|null  $value
     * @return array<int, string>
     */
    private function vacationDays(?array $value): array
    {
        return collect($value ?? [])
            ->filter(fn ($day) => is_string($day) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) === 1)
            ->unique()
            ->sort()
            ->values()
            ->take(400)
            ->all();
    }
}
