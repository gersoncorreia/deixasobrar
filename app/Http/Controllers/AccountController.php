<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    private function getUser(): User
    {
        return Auth::user() ?? User::first();
    }

    public function index(Request $request): Response|JsonResponse
    {
        $user = $this->getUser();

        $accounts = $user->accounts()
            ->withCount('transactions')
            ->orderBy('id', 'asc')
            ->get();

        $totalBalance = (float) $accounts->sum('current_balance');

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'accounts' => $accounts,
                'total_balance' => round($totalBalance, 2),
            ]);
        }

        return Inertia::render('Accounts/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'accounts' => $accounts,
            'totalBalance' => round($totalBalance, 2),
            'accountTypes' => [
                ['value' => AccountType::Checking->value, 'label' => AccountType::Checking->label()],
                ['value' => AccountType::Savings->value, 'label' => AccountType::Savings->label()],
                ['value' => AccountType::CreditCard->value, 'label' => AccountType::CreditCard->label()],
                ['value' => AccountType::Cash->value, 'label' => AccountType::Cash->label()],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:checking,savings,credit_card,cash'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'current_balance' => ['required', 'numeric'],
        ]);

        $account = $user->accounts()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conta criada com sucesso!',
                'account' => $account,
            ], 201);
        }

        return back()->with('success', 'Conta criada com sucesso!');
    }

    public function update(Request $request, Account $account): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        if ($account->user_id !== $user->id) {
            abort(403, 'Acesso não autorizado a esta conta.');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'string', 'in:checking,savings,credit_card,cash'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'current_balance' => ['sometimes', 'required', 'numeric'],
        ]);

        $account->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conta atualizada com sucesso!',
                'account' => $account,
            ]);
        }

        return back()->with('success', 'Conta atualizada com sucesso!');
    }

    public function destroy(Request $request, Account $account): JsonResponse|RedirectResponse
    {
        $user = $this->getUser();

        if ($account->user_id !== $user->id) {
            abort(403, 'Acesso não autorizado a esta conta.');
        }

        // Dissociar transações e histórico para manter integridade
        $account->transactions()->delete();
        $account->statementImports()->delete();
        $account->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conta e lançamentos excluídos com sucesso!',
            ]);
        }

        return back()->with('success', 'Conta excluída com sucesso!');
    }
}
