<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\StatementImport;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 1. Total Metrics
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $newUsersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

        // 2. Subscriptions Metrics & MRR Calculation
        $subscriptions = Subscription::with('user')->get();
        $activeSubscriptions = $subscriptions->where('status', 'active');

        $planPrices = [
            SubscriptionPlan::Free->value => 0.00,
            SubscriptionPlan::ProMonthly->value => 19.90,
            SubscriptionPlan::ProAnnual->value => 179.90 / 12, // MRR fraction ~14.99
            SubscriptionPlan::Family->value => 29.90,
        ];

        $mrr = 0.00;
        foreach ($activeSubscriptions as $sub) {
            $planVal = $sub->plan_tier instanceof SubscriptionPlan ? $sub->plan_tier->value : (string) $sub->plan_tier;
            $mrr += $planPrices[$planVal] ?? 0.00;
        }

        $arr = $mrr * 12;

        // Plan distribution
        $plansBreakdown = [
            'free' => $subscriptions->where('plan_tier', SubscriptionPlan::Free->value)->count(),
            'pro_mensal' => $subscriptions->where('plan_tier', SubscriptionPlan::ProMonthly->value)->count(),
            'pro_anual' => $subscriptions->where('plan_tier', SubscriptionPlan::ProAnnual->value)->count(),
            'familia' => $subscriptions->where('plan_tier', SubscriptionPlan::Family->value)->count(),
        ];

        // 3. Platform Health Metrics
        $totalTransactions = Transaction::count();
        $totalImports = StatementImport::count();
        $totalAccounts = Account::count();

        // 4. Recent Registered Users
        $recentUsers = User::with('activeSubscription')
            ->orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'is_active' => (bool) $u->is_active,
                    'is_admin' => (bool) $u->is_admin,
                    'created_at' => $u->created_at?->format('d/m/Y H:i'),
                    'plan' => $u->activeSubscription?->plan_tier?->title() ?? 'Gratuito',
                    'plan_status' => $u->activeSubscription?->status ?? 'active',
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'adminUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ],
            'metrics' => [
                'mrr' => round($mrr, 2),
                'arr' => round($arr, 2),
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'newUsersThisMonth' => $newUsersThisMonth,
                'totalTransactions' => $totalTransactions,
                'totalImports' => $totalImports,
                'totalAccounts' => $totalAccounts,
                'plansBreakdown' => $plansBreakdown,
            ],
            'recentUsers' => $recentUsers,
        ]);
    }
}
