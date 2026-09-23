<?php

namespace App\Models;

use App\Enums\CategoryGroupType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'group_type',
        'icon',
        'color_hex',
        'budget_ceiling',
        'due_day',
    ];

    protected $casts = [
        'group_type' => CategoryGroupType::class,
        'budget_ceiling' => 'decimal:2',
        'due_day' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isFixedObligation(): bool
    {
        return in_array($this->group_type, [
            CategoryGroupType::FixedExpense,
            CategoryGroupType::DebtInstallment,
        ]);
    }

    public function scopeFixedObligations(Builder $query): Builder
    {
        return $query->whereIn('group_type', [
            CategoryGroupType::FixedExpense,
            CategoryGroupType::DebtInstallment,
        ]);
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id');
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }
}
