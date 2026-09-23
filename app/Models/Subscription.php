<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_tier',
        'status',
        'current_period_end',
        'payment_method',
        'asaas_subscription_id',
        'asaas_payment_id',
        'pix_qrcode',
        'pix_payload',
        'pix_expiration',
        'invoice_url',
    ];

    protected $casts = [
        'plan_tier' => SubscriptionPlan::class,
        'current_period_end' => 'datetime',
        'pix_expiration' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }
}
