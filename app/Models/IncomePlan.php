<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'regime',
    'side_activity',
    'activity',
    'exp_mode',
    'exp_real',
    'carry_amount',
    'carry_month',
])]
class IncomePlan extends Model
{
    protected function casts(): array
    {
        return [
            'side_activity' => 'boolean',
            'exp_real' => 'decimal:2',
            'carry_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(IncomeSource::class)->orderBy('position')->orderBy('id');
    }
}
