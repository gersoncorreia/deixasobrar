<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'expected_amount',
        'due_day',
        'frequency',
        'payment_channel',
        'is_active',
        'last_detected_date',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'due_day' => 'integer',
        'is_active' => 'boolean',
        'last_detected_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
