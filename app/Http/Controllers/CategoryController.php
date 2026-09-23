<?php

namespace App\Http\Controllers;

use App\Enums\CategoryGroupType;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Render the dedicated Fixed Bills & Budgets page
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        return Inertia::render('FixedBills/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Get list of fixed obligations and budget metrics for the current cycle
     */
    public function getFixedBills(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $now->copy()->endOfMonth()->format('Y-m-d');

        // User custom categories
        $userCustomCategories = Category::where('user_id', $user->id)
            ->fixedObligations()
            ->orderBy('due_day', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        $userCustomNames = $userCustomCategories->pluck('name')->all();

        // System defaults not overridden
        $defaultCategories = Category::whereNull('user_id')
            ->fixedObligations()
            ->whereNotIn('name', $userCustomNames)
            ->orderBy('due_day', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $allCategories = $userCustomCategories->concat($defaultCategories);

        $list = [];
        $totalPlanned = 0.00;
        $totalPaid = 0.00;
        $totalPending = 0.00;

        foreach ($allCategories as $cat) {
            $ceiling = (float) ($cat->budget_ceiling ?? 0);
            
            // Negative transactions in current month
            $paid = (float) $user->transactions()
                ->where('category_id', $cat->id)
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->where('amount', '<', 0)
                ->sum('amount');
            $paidPositive = abs($paid);

            $pending = max(0.00, $ceiling - $paidPositive);
            $isPaid = ($ceiling > 0 && $paidPositive >= $ceiling);

            $status = 'unplanned';
            if ($ceiling > 0) {
                if ($isPaid) {
                    $status = 'paid';
                } elseif ($paidPositive > 0) {
                    $status = 'partial';
                } else {
                    $status = 'pending';
                }
            }

            if ($ceiling > 0) {
                $totalPlanned += $ceiling;
                $totalPaid += min($paidPositive, $ceiling);
                $totalPending += $pending;
            }

            $list[] = [
                'id' => $cat->id,
                'user_id' => $cat->user_id,
                'name' => $cat->name,
                'group_type' => $cat->group_type->value,
                'group_label' => $cat->group_type->label(),
                'icon' => $cat->icon,
                'color_hex' => $cat->color_hex,
                'budget_ceiling' => $ceiling > 0 ? $ceiling : null,
                'due_day' => $cat->due_day,
                'paid_amount' => round($paidPositive, 2),
                'pending_amount' => round($pending, 2),
                'is_paid' => $isPaid,
                'status' => $status,
                'is_custom' => $cat->user_id !== null,
            ];
        }

        $percentShielded = $totalPlanned > 0 ? (int) round(($totalPaid / $totalPlanned) * 100) : 0;

        return response()->json([
            'success' => true,
            'fixed_bills' => $list,
            'summary' => [
                'total_planned' => round($totalPlanned, 2),
                'total_paid' => round($totalPaid, 2),
                'total_pending' => round($totalPending, 2),
                'percent_shielded' => min(100, $percentShielded),
                'count_planned' => count(array_filter($list, fn($c) => $c['budget_ceiling'] > 0)),
                'count_paid' => count(array_filter($list, fn($c) => $c['is_paid'])),
            ],
        ]);
    }

    /**
     * Create a new custom category / fixed bill
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'group_type' => ['required', 'string', 'in:fixed_expense,routine_variable,habits_lifestyle,debt_installment,income'],
            'budget_ceiling' => ['nullable', 'numeric', 'min:0'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'max:7'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'group_type' => $validated['group_type'],
            'budget_ceiling' => $validated['budget_ceiling'] ?? null,
            'due_day' => $validated['due_day'] ?? null,
            'icon' => $validated['icon'] ?? 'file-text',
            'color_hex' => $validated['color_hex'] ?? '#3b82f6',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conta / Categoria cadastrada com sucesso!',
            'category' => $category,
        ], 201);
    }

    /**
     * Update category budget ceiling, due day, name, etc.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'budget_ceiling' => ['nullable', 'numeric', 'min:0'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'max:7'],
        ]);

        /** @var User $user */
        $user = Auth::user() ?? User::first();

        // If category is a global default, fork it into a user-owned custom category
        if ($category->user_id === null) {
            $userCategory = Category::create([
                'user_id' => $user->id,
                'name' => $validated['name'] ?? $category->name,
                'group_type' => $category->group_type,
                'icon' => $validated['icon'] ?? $category->icon,
                'color_hex' => $validated['color_hex'] ?? $category->color_hex,
                'budget_ceiling' => array_key_exists('budget_ceiling', $validated) ? $validated['budget_ceiling'] : $category->budget_ceiling,
                'due_day' => array_key_exists('due_day', $validated) ? $validated['due_day'] : $category->due_day,
            ]);

            // Reassign any existing transactions of this user to the personalized category
            $user->transactions()->where('category_id', $category->id)->update(['category_id' => $userCategory->id]);

            return response()->json([
                'success' => true,
                'message' => 'Conta fixa personalizada com sucesso!',
                'category' => $userCategory,
            ]);
        }

        // Ensure user owns this category
        if ($category->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Não autorizado.'], 403);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Conta fixa atualizada com sucesso!',
            'category' => $category,
        ]);
    }

    /**
     * Delete custom category
     */
    public function destroy(Category $category): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user() ?? User::first();

        if ($category->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Categorias padrão não podem ser excluídas.'], 403);
        }

        // Dissociate transactions before delete
        $user->transactions()->where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conta fixa excluída com sucesso!',
        ]);
    }
}
