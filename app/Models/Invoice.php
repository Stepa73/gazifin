<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'client_id',
    'number',
    'issue_date',
    'due_date',
    'status',
    'notes',
    'subtotal',
    'vat_rate',
    'vat_amount',
    'total',
    'is_vat_payer',
    'variable_symbol',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_vat_payer' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function effectiveVariableSymbol(): string
    {
        if ($this->variable_symbol) {
            return $this->variable_symbol;
        }

        $digits = preg_replace('/\D/', '', $this->number) ?? '';

        return substr($digits, 0, 10);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Odesláno',
            'paid' => 'Zaplaceno',
            default => 'Koncept',
        };
    }

    public function mobileStatusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Odesláno',
            'paid' => 'Uhrazeno',
            default => 'Neodesláno',
        };
    }

    public function mobileStatusClass(): string
    {
        return match ($this->status) {
            'sent' => 'text-gray-500',
            'paid' => 'text-green-600',
            default => 'text-orange-500',
        };
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast();
    }
}
