<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_scan_id',
        'item_name',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'item_category',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function receiptScan(): BelongsTo
    {
        return $this->belongsTo(ReceiptScan::class);
    }
}
