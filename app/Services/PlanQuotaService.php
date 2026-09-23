<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\ReceiptScan;
use App\Models\StatementImport;
use App\Models\SystemSetting;
use App\Models\User;

class PlanQuotaService
{
    /**
     * Checks if a user can perform an OCR Scan.
     *
     * @return array [can_scan => bool, current_usage => int, limit => int|null, plan => string]
     */
    public function checkOcrQuota(User $user): array
    {
        // Admin Master has unlimited access
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'current_usage' => 0,
                'limit' => null,
                'remaining' => 9999,
                'plan' => 'admin',
                'is_unlimited' => true,
            ];
        }

        $subscription = $user->activeSubscription;
        $isPro = $subscription && $subscription->isActive() && in_array(
            $subscription->plan_tier instanceof SubscriptionPlan ? $subscription->plan_tier->value : $subscription->plan_tier, 
            ['pro_mensal', 'pro_anual', 'familia']
        );

        $planName = $isPro ? ($subscription->plan_tier instanceof SubscriptionPlan ? $subscription->plan_tier->value : $subscription->plan_tier) : 'free';

        // Read configured limits from SystemSettings or fallback defaults
        $freeLimit = (int) (SystemSetting::get('plan_free_ocr_limit') ?: 3);
        $proLimit = (int) (SystemSetting::get('plan_pro_ocr_limit') ?: 100);

        $limit = $isPro ? $proLimit : $freeLimit;

        // Count scans made in the current calendar month
        $startOfMonth = now()->startOfMonth();
        $currentUsage = ReceiptScan::where('user_id', $user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $remaining = max(0, $limit - $currentUsage);
        $allowed = $remaining > 0;

        return [
            'allowed' => $allowed,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $remaining,
            'plan' => $planName,
            'is_unlimited' => false,
            'upgrade_required' => !$allowed && !$isPro,
        ];
    }

    /**
     * Checks if a user can import statements.
     */
    public function checkStatementImportQuota(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'current_usage' => 0,
                'limit' => null,
                'remaining' => 9999,
                'plan' => 'admin',
                'is_unlimited' => true,
            ];
        }

        $subscription = $user->activeSubscription;
        $isPro = $subscription && $subscription->isActive() && in_array(
            $subscription->plan_tier instanceof SubscriptionPlan ? $subscription->plan_tier->value : $subscription->plan_tier, 
            ['pro_mensal', 'pro_anual', 'familia']
        );

        // Pro subscribers have unlimited statement imports
        if ($isPro) {
            return [
                'allowed' => true,
                'current_usage' => 0,
                'limit' => null,
                'remaining' => 9999,
                'plan' => 'pro',
                'is_unlimited' => true,
            ];
        }

        $freeLimit = (int) (SystemSetting::get('plan_free_statement_limit') ?: 3);
        $startOfMonth = now()->startOfMonth();
        $currentUsage = StatementImport::where('user_id', $user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $remaining = max(0, $freeLimit - $currentUsage);
        $allowed = $remaining > 0;

        return [
            'allowed' => $allowed,
            'current_usage' => $currentUsage,
            'limit' => $freeLimit,
            'remaining' => $remaining,
            'plan' => 'free',
            'is_unlimited' => false,
            'upgrade_required' => !$allowed,
        ];
    }

    /**
     * Checks if a user is eligible for AI-powered statement classification.
     */
    public function checkAiStatementQuota(User $user): array
    {
        // Global toggle in Admin settings
        $isGloballyEnabled = (bool) (SystemSetting::get('enable_ai_statement_classification', '1') === '1');
        if (!$isGloballyEnabled) {
            return [
                'allowed' => false,
                'reason' => 'Recurso de IA para extratos temporariamente desativado pelo administrador.',
                'remaining' => 0,
                'plan' => 'disabled',
            ];
        }

        // Admin Master has unlimited access
        if ($user->isAdmin()) {
            return [
                'allowed' => true,
                'current_usage' => 0,
                'limit' => null,
                'remaining' => 9999,
                'plan' => 'admin',
                'is_unlimited' => true,
            ];
        }

        $subscription = $user->activeSubscription;
        $isPro = $subscription && $subscription->isActive() && in_array(
            $subscription->plan_tier instanceof SubscriptionPlan ? $subscription->plan_tier->value : $subscription->plan_tier, 
            ['pro_mensal', 'pro_anual', 'familia']
        );

        $freeLimit = (int) (SystemSetting::get('plan_free_ai_statement_limit') ?: 1);
        $proLimit = (int) (SystemSetting::get('plan_pro_ai_statement_limit') ?: 20);

        $limit = $isPro ? $proLimit : $freeLimit;

        // Current month's completed AI-enriched statement imports
        $startOfMonth = now()->startOfMonth();
        $currentUsage = StatementImport::where('user_id', $user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->where('status', 'completed')
            ->where('total_records', '>', 0)
            ->count();

        $remaining = max(0, $limit - $currentUsage);
        $allowed = $remaining > 0;

        return [
            'allowed' => $allowed,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $remaining,
            'plan' => $isPro ? 'pro' : 'free',
            'is_unlimited' => false,
        ];
    }
}
