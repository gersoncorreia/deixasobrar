<?php

namespace App\Actions\Financial;

use App\Enums\CategoryGroupType;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;

class CalculateSafeToSpendAction
{
    /**
     * Calculates Safe-to-Spend (Teto Diário Seguro)
     *
     * @param User $user
     * @param int $paydayDay Day of month when user receives salary (e.g. 5, 20, 28)
     * @param float $safetyReserve Minimum reserve to maintain untouched (e.g. R$ 200)
     * @return array
     */
    public function execute(User $user, int $paydayDay = 5, float $safetyReserve = 0.00): array
    {
        // 1. Current total balance across all accounts
        $currentBalance = (float) $user->accounts()->sum('current_balance');

        // 2. Pending fixed bills for current cycle
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get applicable fixed categories for the user (user custom + defaults not overridden)
        $userCustomCategories = Category::where('user_id', $user->id)
            ->fixedObligations()
            ->get();
        $userCustomNames = $userCustomCategories->pluck('name')->all();

        $defaultCategories = Category::whereNull('user_id')
            ->fixedObligations()
            ->whereNotIn('name', $userCustomNames)
            ->get();

        $allFixedCategories = $userCustomCategories->concat($defaultCategories);

        $pendingFixedBills = 0.00;

        foreach ($allFixedCategories as $cat) {
            $ceiling = (float) ($cat->budget_ceiling ?? 0);
            if ($ceiling <= 0) {
                continue;
            }

            // Calculate paid amount in this category during the current month (negative transactions)
            $paid = (float) $user->transactions()
                ->where('category_id', $cat->id)
                ->whereBetween('transaction_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('amount', '<', 0)
                ->sum('amount');

            $paidPositive = abs($paid);
            $pendingForCat = max(0.00, $ceiling - $paidPositive);
            $pendingFixedBills += $pendingForCat;
        }

        // 3. Days remaining until next payday
        $today = $now->day;
        if ($today < $paydayDay) {
            $daysRemaining = $paydayDay - $today;
        } else {
            // Payday is in next month
            $daysInMonth = $now->daysInMonth;
            $daysRemaining = ($daysInMonth - $today) + $paydayDay;
        }
        $daysRemaining = max(1, $daysRemaining);

        // 4. Safe To Spend Formula:
        // (Current Balance - Pending Fixed Bills - Safety Reserve) / Days Remaining
        $availableCapital = $currentBalance - $pendingFixedBills - $safetyReserve;
        $dailyCeiling = $availableCapital > 0 ? ($availableCapital / $daysRemaining) : 0.00;

        // Health Status Indicator
        $status = 'healthy'; // emerald
        if ($dailyCeiling <= 0) {
            $status = 'critical'; // rose
        } elseif ($dailyCeiling < 30.00) {
            $status = 'warning'; // amber
        }

        return [
            'current_balance' => round($currentBalance, 2),
            'pending_fixed_bills' => round($pendingFixedBills, 2),
            'safety_reserve' => round($safetyReserve, 2),
            'available_capital' => round($availableCapital, 2),
            'days_remaining' => $daysRemaining,
            'next_payday_day' => $paydayDay,
            'daily_ceiling' => round($dailyCeiling, 2),
            'status' => $status,
        ];
    }
}
