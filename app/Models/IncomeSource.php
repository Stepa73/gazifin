<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'income_plan_id',
    'client_id',
    'name',
    'mode',
    'rate',
    'unit',
    'hours_per_day',
    'payment_lag',
    'pay_day',
    'starts_on',
    'ends_on',
    'fixed_amount',
    'invoice_date',
    'invoice_amount',
    'vacation',
    'position',
])]
class IncomeSource extends Model
{
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'invoice_date' => 'date',
            'rate' => 'decimal:2',
            'hours_per_day' => 'decimal:1',
            'fixed_amount' => 'decimal:2',
            'invoice_amount' => 'decimal:2',
            'vacation' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(IncomePlan::class, 'income_plan_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Tvar, ve kterém zdroj čte kalkulačka v prohlížeči.
     *
     * @return array<string, mixed>
     */
    public function toCalculatorState(): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'name' => $this->name,
            'mode' => $this->mode,
            'rate' => (float) $this->rate,
            'unit' => $this->unit,
            'hoursPerDay' => (float) $this->hours_per_day,
            'lag' => $this->payment_lag,
            'payDay' => $this->pay_day,
            'from' => $this->starts_on?->format('Y-m-d') ?? '',
            'to' => $this->ends_on?->format('Y-m-d') ?? '',
            'fixed' => (float) $this->fixed_amount,
            'date' => $this->invoice_date?->format('Y-m-d') ?? '',
            'amount' => (float) $this->invoice_amount,
            'vacation' => $this->vacation ?? array_fill(0, 12, 0),
        ];
    }
}
