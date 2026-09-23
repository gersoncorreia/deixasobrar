<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'category_id',
        'statement_import_id',
        'transaction_date',
        'description',
        'raw_statement_text',
        'document_number',
        'amount',
        'type',
        'status',
        'is_recurring',
        'is_leak',
        'leak_reason',
        'installment_number',
        'installment_total',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'is_recurring' => 'boolean',
        'is_leak' => 'boolean',
        'installment_number' => 'integer',
        'installment_total' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(StatementImport::class);
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Expense);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Income);
    }

    public function scopeLeaks(Builder $query): Builder
    {
        return $query->where('is_leak', true);
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
