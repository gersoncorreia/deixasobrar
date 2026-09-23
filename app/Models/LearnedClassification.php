<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnedClassification extends Model
{
    protected $fillable = [
        'user_id',
        'normalized_description',
        'category_id',
        'category_name',
        'is_leak',
        'leak_reason',
        'source',
    ];

    protected $casts = [
        'is_leak' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Normalizes a bank transaction description into a consistent lookup key.
     */
    public static function normalizeKey(string $description): string
    {
        $clean = mb_strtolower(trim($description), 'UTF-8');
        // Remove excessive punctuation, numbers of dates or timestamps at end
        $clean = preg_replace('/[0-9]{2}\/[0-9]{2}(\/[0-9]{2,4})?/', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }
}
