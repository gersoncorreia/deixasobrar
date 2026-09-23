<?php

namespace App\Http\Controllers;

use App\Actions\Financial\CalculateSafeToSpendAction;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected CalculateSafeToSpendAction $calculateSafeToSpend
    ) {}

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Admin Master cannot access subscriber dashboard for himself (must stay in Admin Master)
        if ($user && $user->isAdmin() && !session()->has('admin_impersonator_id')) {
            return redirect()->route('admin.dashboard');
        }

        // If not logged in in local dev, provide or create standard demo user
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'usuario@deixasobrar.com.br'],
                [
                    'name' => 'Gerson Silva',
                    'password' => bcrypt('secret123'),
                ]
            );

            // Ensure demo account exists
            if ($user->accounts()->count() === 0) {
                $user->accounts()->createMany([
                    [
                        'name' => 'Conta Corrente Banco do Brasil',
                        'type' => AccountType::Checking,
                        'current_balance' => 2450.00,
                        'bank_name' => 'Banco do Brasil',
                    ],
                    [
                        'name' => 'Conta Digital Nubank',
                        'type' => AccountType::Checking,
                        'current_balance' => 820.50,
                        'bank_name' => 'Nubank',
                    ],
                ]);
            }
        }

        // 1. Calculate Safe To Spend (Formula oficial)
        $paydayDay = (int) ($user->payday_day ?? 5);
        $safetyReserve = (float) ($user->safety_reserve ?? 200.00);
        $safeToSpend = $this->calculateSafeToSpend->execute($user, paydayDay: $paydayDay, safetyReserve: $safetyReserve);

        // 2. Fetch Accounts
        $accounts = $user->accounts()->get();

        // 3. Transactions Query with Filters & Pagination
        $query = $user->transactions()
            ->with(['category', 'account'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            if ($type === 'leak') {
                $query->where('is_leak', true);
            } elseif (in_array($type, ['income', 'expense', 'transfer'])) {
                $query->where('type', $type);
            }
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($accountId = $request->input('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($month = $request->input('month')) {
            $query->where('transaction_date', 'like', "{$month}%");
        }

        $transactions = $query->paginate(15)->withQueryString();

        // 4. Calculate Leak Radar Metrics
        $totalLeaksAmount = (float) $user->transactions()
            ->where('is_leak', true)
            ->sum('amount');

        $leaksCount = $user->transactions()
            ->where('is_leak', true)
            ->count();

        // 5. Fetch Categories
        $categories = Category::forUser($user->id)->get();

        // 6. Upcoming Fixed Bills Timeline (Contas com vencimento no ciclo)
        $now = \Carbon\Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $userCustomCategories = Category::where('user_id', $user->id)
            ->fixedObligations()
            ->get();
        $userCustomNames = $userCustomCategories->pluck('name')->all();

        $defaultCategories = Category::whereNull('user_id')
            ->fixedObligations()
            ->whereNotIn('name', $userCustomNames)
            ->get();

        $allFixedCategories = $userCustomCategories->concat($defaultCategories);

        $upcomingBills = [];
        $currentDay = $now->day;

        foreach ($allFixedCategories as $cat) {
            $ceiling = (float) ($cat->budget_ceiling ?? 0);
            if ($ceiling <= 0) continue;

            $paid = (float) $user->transactions()
                ->where('category_id', $cat->id)
                ->whereBetween('transaction_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->where('amount', '<', 0)
                ->sum('amount');

            $paidPositive = abs($paid);
            $pending = max(0.00, $ceiling - $paidPositive);
            $isPaid = $paidPositive >= ($ceiling * 0.9); // Consider paid if >= 90%
            $dueDay = (int) ($cat->due_day ?? $paydayDay);

            $daysUntilDue = $dueDay >= $currentDay 
                ? $dueDay - $currentDay 
                : ($now->daysInMonth - $currentDay) + $dueDay;

            $upcomingBills[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'due_day' => $dueDay,
                'budget_ceiling' => $ceiling,
                'paid_amount' => round($paidPositive, 2),
                'pending_amount' => round($pending, 2),
                'is_paid' => $isPaid,
                'days_until_due' => $daysUntilDue,
                'color_hex' => $cat->color_hex ?? '#3b82f6',
            ];
        }

        // Sort upcoming bills by days until due (closest first)
        usort($upcomingBills, fn($a, $b) => $a['days_until_due'] <=> $b['days_until_due']);

        // 7. Available Months for Filtering (Cross-database compatible)
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $availableMonths = $user->transactions()
            ->selectRaw("DISTINCT {$monthExpr} as month")
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->filter()
            ->values();

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'payday_day' => $paydayDay,
                'safety_reserve' => $safetyReserve,
            ],
            'safeToSpend' => $safeToSpend,
            'accounts' => $accounts,
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'type', 'category_id', 'account_id', 'month']),
            'availableMonths' => $availableMonths,
            'leaksSummary' => [
                'totalAmount' => abs($totalLeaksAmount),
                'count' => $leaksCount,
            ],
            'categories' => $categories,
            'upcomingBills' => $upcomingBills,
        ]);
    }

    public function updateCycle(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'payday_day' => ['required', 'integer', 'min:1', 'max:31'],
            'safety_reserve' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();
        $user->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Configurações de ciclo de renda atualizadas com sucesso!',
                'user' => $user,
            ]);
        }

        return back()->with('success', 'Configurações de ciclo de renda atualizadas com sucesso!');
    }
}
