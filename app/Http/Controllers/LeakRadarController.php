<?php

namespace App\Http\Controllers;

use App\Actions\Financial\CalculateSafeToSpendAction;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeakRadarController extends Controller
{
    public function __construct(
        protected CalculateSafeToSpendAction $calculateSafeToSpend
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        // 1. Safe to Spend Context
        $paydayDay = (int) ($user->payday_day ?? 5);
        $safetyReserve = (float) ($user->safety_reserve ?? 200.00);
        $safeToSpend = $this->calculateSafeToSpend->execute($user, paydayDay: $paydayDay, safetyReserve: $safetyReserve);

        // 2. Base leaks query with filters
        $leaksQuery = $user->transactions()
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        // Status filter: 'active' (default leaks), 'inactive' (non-leaks), 'all' (everything)
        $status = $request->input('status', 'active');
        if ($status === 'active') {
            $leaksQuery->where('is_leak', true);
        } elseif ($status === 'inactive') {
            $leaksQuery->where('is_leak', false);
        }
        // if status === 'all', do not filter by is_leak

        // Category filter
        if ($categoryId = $request->input('category_id')) {
            $leaksQuery->where('category_id', $categoryId);
        }

        // Date Range filters
        if ($startDate = $request->input('start_date')) {
            $leaksQuery->where('transaction_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $leaksQuery->where('transaction_date', '<=', $endDate);
        }

        // Month filter
        if ($month = $request->input('month')) {
            $leaksQuery->where('transaction_date', 'like', "{$month}%");
        }

        $totalLeaksAmount = abs((float) $user->transactions()->where('is_leak', true)->sum('amount'));
        $totalLeaksCount = (int) $user->transactions()->where('is_leak', true)->count();

        // 3. Breakdown by category
        $categoriesBreakdown = $user->transactions()
            ->where('is_leak', true)
            ->select('category_id', DB::raw('SUM(ABS(amount)) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($item) use ($totalLeaksAmount) {
                $categoryName = $item->category ? $item->category->name : 'Sem categoria';
                $categoryIcon = $item->category ? $item->category->icon : 'Flame';
                $categoryColor = $item->category ? $item->category->color : '#f59e0b';
                $percentage = $totalLeaksAmount > 0 ? round(($item->total_amount / $totalLeaksAmount) * 100, 1) : 0;

                return [
                    'category_id' => $item->category_id,
                    'name' => $categoryName,
                    'icon' => $categoryIcon,
                    'color' => $categoryColor,
                    'total_amount' => (float) $item->total_amount,
                    'count' => (int) $item->count,
                    'percentage' => $percentage,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // 4. Paginated transactions (10 por página conforme solicitado)
        $transactions = $leaksQuery->paginate(10)->withQueryString();

        // 5. Available months
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $availableMonths = $user->transactions()
            ->where('is_leak', true)
            ->selectRaw("DISTINCT {$monthExpr} as month")
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->filter()
            ->values();

        // 6. User categories
        $categories = Category::forUser($user->id)->orderBy('name')->get();

        return Inertia::render('Leaks/Index', [
            'safeToSpend' => $safeToSpend,
            'summary' => [
                'totalAmount' => $totalLeaksAmount,
                'count' => $totalLeaksCount,
                'daysRemaining' => $safeToSpend['days_remaining'] ?? 1,
                'dailyImpact' => ($safeToSpend['days_remaining'] ?? 1) > 0 
                    ? round($totalLeaksAmount / ($safeToSpend['days_remaining'] ?? 1), 2)
                    : 0,
            ],
            'categoriesBreakdown' => $categoriesBreakdown,
            'categories' => $categories,
            'transactions' => $transactions,
            'availableMonths' => $availableMonths,
            'filters' => $request->only(['category_id', 'month', 'start_date', 'end_date', 'status']),
        ]);
    }
}
