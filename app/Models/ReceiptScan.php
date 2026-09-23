<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'scan_type',
        'image_path',
        'merchant_name',
        'merchant_tax_id',
        'purchased_at',
        'total_amount',
        'payment_method_detected',
        'card_last_digits',
        'raw_ocr_payload',
        'match_status',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'raw_ocr_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReceiptItem::class);
    }
}
