<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $accounts = $user->accounts()->get();
        $categories = Category::forUser($user->id)->get();

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

        $transactions = $query->paginate(20)->withQueryString();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        $availableMonths = $user->transactions()
            ->selectRaw("DISTINCT {$monthExpr} as month")
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->filter()
            ->values();

        $totalLeaksAmount = (float) $user->transactions()
            ->where('is_leak', true)
            ->sum('amount');

        $leaksCount = $user->transactions()
            ->where('is_leak', true)
            ->count();

        return Inertia::render('Transactions/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'accounts' => $accounts,
            'categories' => $categories,
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'type', 'category_id', 'account_id', 'month']),
            'availableMonths' => $availableMonths,
            'leaksSummary' => [
                'totalAmount' => abs($totalLeaksAmount),
                'count' => $leaksCount,
            ],
        ]);
    }
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'not_in:0'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'transaction_date' => ['required', 'date'],
            'is_leak' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $account = Account::where('user_id', $user->id)->findOrFail($validated['account_id']);

        $rawAmount = (float) $validated['amount'];
        $type = TransactionType::from($validated['type']);

        $signedAmount = match ($type) {
            TransactionType::Expense => -abs($rawAmount),
            TransactionType::Income => abs($rawAmount),
            TransactionType::Transfer => -abs($rawAmount),
        };

        $transaction = DB::transaction(function () use ($user, $account, $validated, $signedAmount, $type) {
            $tx = Transaction::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'category_id' => $validated['category_id'] ?? null,
                'transaction_date' => $validated['transaction_date'],
                'description' => trim($validated['description']),
                'amount' => $signedAmount,
                'type' => $type,
                'status' => 'confirmed',
                'is_leak' => (bool) ($validated['is_leak'] ?? false),
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
            ]);

            $account->increment('current_balance', $signedAmount);

            return $tx;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lançamento adicionado com sucesso!',
                'transaction' => $transaction,
            ]);
        }

        return back()->with('success', 'Lançamento adicionado com sucesso!');
    }

    public function update(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($transaction->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_leak' => ['sometimes', 'boolean'],
            'is_recurring' => ['sometimes', 'boolean'],
            'transaction_date' => ['sometimes', 'required', 'date'],
        ]);

        $transaction->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lançamento atualizado!',
                'transaction' => $transaction->fresh(['category', 'account']),
            ]);
        }

        return back()->with('success', 'Lançamento atualizado!');
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($transaction->user_id !== $user->id) {
            abort(403);
        }

        DB::transaction(function () use ($transaction) {
            // Revert account balance (if expense, it was negative, so subtracting negative increases balance)
            $account = $transaction->account;
            if ($account) {
                $account->decrement('current_balance', $transaction->amount);
            }
            $transaction->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lançamento removido com sucesso.',
            ]);
        }

        return back()->with('success', 'Lançamento removido com sucesso.');
    }
}
