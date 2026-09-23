<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    /**
     * Handles Asaas webhook notification events.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify Asaas Webhook Token if configured
        $configuredToken = SystemSetting::get('asaas_webhook_token') ?: env('ASAAS_WEBHOOK_TOKEN');
        if (!empty($configuredToken)) {
            $incomingToken = $request->header('asaas-access-token');
            if ($incomingToken !== $configuredToken) {
                Log::warning('Asaas Webhook rejected: Invalid asaas-access-token header.');
                return response()->json(['error' => 'Unauthorized token'], 401);
            }
        }

        $event = $request->input('event');
        $payment = $request->input('payment') ?? [];

        Log::info("Asaas Webhook received: [{$event}]", ['payment' => $payment]);

        $asaasSubId = $payment['subscription'] ?? null;
        $asaasPaymentId = $payment['id'] ?? null;
        $customerId = $payment['customer'] ?? null;

        // Locate subscription either by Asaas subscription ID, payment ID, or Customer
        $subscription = null;
        if ($asaasSubId) {
            $subscription = Subscription::where('asaas_subscription_id', $asaasSubId)->first();
        }
        if (!$subscription && $asaasPaymentId) {
            $subscription = Subscription::where('asaas_payment_id', $asaasPaymentId)->first();
        }
        if (!$subscription && $customerId) {
            $user = User::where('asaas_customer_id', $customerId)->first();
            $subscription = $user?->activeSubscription;
        }

        if (!$subscription) {
            Log::info("Asaas Webhook: No matching local subscription found for payment {$asaasPaymentId} / sub {$asaasSubId}.");
            return response()->json(['received' => true, 'matched' => false]);
        }

        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                $isAnnual = $subscription->plan_tier === SubscriptionPlan::ProAnnual;
                $subscription->update([
                    'status' => 'active',
                    'current_period_end' => $isAnnual ? now()->addYear() : now()->addMonth(),
                    'asaas_payment_id' => $asaasPaymentId ?: $subscription->asaas_payment_id,
                ]);
                Log::info("Subscription #{$subscription->id} activated/renewed for User #{$subscription->user_id}.");
                break;

            case 'PAYMENT_OVERDUE':
                $subscription->update([
                    'status' => 'past_due',
                ]);
                Log::info("Subscription #{$subscription->id} marked past_due for User #{$subscription->user_id}.");
                break;

            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_DELETED':
            case 'SUBSCRIPTION_INACTIVATED':
            case 'SUBSCRIPTION_DELETED':
                $subscription->update([
                    'status' => 'canceled',
                    'plan_tier' => SubscriptionPlan::Free,
                ]);
                Log::info("Subscription #{$subscription->id} canceled and reverted to free for User #{$subscription->user_id}.");
                break;
        }

        return response()->json(['received' => true, 'event' => $event]);
    }
}
