<?php

namespace App\Models;

use App\Enums\BankInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatementImport extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'file_name',
        'detected_bank',
        'total_records',
        'imported_records',
        'skipped_records',
        'status',
    ];

    protected $casts = [
        'detected_bank' => BankInstitution::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
