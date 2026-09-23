<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = User::with('activeSubscription')
            ->withCount(['accounts', 'transactions']);

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->has('status') && $request->query('status') !== 'all') {
            $status = $request->query('status') === 'active';
            $query->where('is_active', $status);
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $usersList = $users->through(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_admin' => (bool) $u->is_admin,
                'is_active' => (bool) $u->is_active,
                'accounts_count' => $u->accounts_count,
                'transactions_count' => $u->transactions_count,
                'payday_day' => $u->payday_day,
                'safety_reserve' => $u->safety_reserve,
                'created_at' => $u->created_at?->format('d/m/Y H:i'),
                'subscription' => [
                    'plan' => $u->activeSubscription?->plan_tier instanceof SubscriptionPlan 
                        ? $u->activeSubscription->plan_tier->value 
                        : ($u->activeSubscription?->plan_tier ?? 'free'),
                    'plan_name' => $u->activeSubscription?->plan_tier instanceof SubscriptionPlan 
                        ? $u->activeSubscription->plan_tier->title() 
                        : 'Gratuito',
                    'status' => $u->activeSubscription?->status ?? 'active',
                ],
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'adminUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ],
            'users' => $usersList,
            'filters' => $request->only(['search', 'status']),
            'availablePlans' => [
                ['value' => 'free', 'label' => 'Gratuito'],
                ['value' => 'pro_mensal', 'label' => 'Pro Mensal (R$ 19,90)'],
                ['value' => 'pro_anual', 'label' => 'Pro Anual (R$ 179,90)'],
                ['value' => 'familia', 'label' => 'Plano Família (R$ 29,90)'],
            ],
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse|JsonResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Você não pode desativar sua própria conta de administrador.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $msg = $user->is_active ? 'Usuário ativado com sucesso!' : 'Usuário suspenso com sucesso!';

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'is_active' => $user->is_active]);
        }

        return back()->with('success', $msg);
    }

    public function updatePlan(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'plan_tier' => ['required', 'string', 'in:free,pro_mensal,pro_anual,familia'],
            'status' => ['required', 'string', 'in:active,trialing,past_due,canceled'],
        ]);

        $sub = $user->activeSubscription;
        if ($sub) {
            $sub->update([
                'plan_tier' => $validated['plan_tier'],
                'status' => $validated['status'],
                'current_period_end' => now()->addMonth(),
            ]);
        } else {
            $user->subscriptions()->create([
                'plan_tier' => $validated['plan_tier'],
                'status' => $validated['status'],
                'current_period_end' => now()->addMonth(),
                'payment_method' => 'admin_granted',
            ]);
        }

        $msg = "Plano do usuário {$user->name} atualizado com sucesso!";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function impersonate(User $user): RedirectResponse
    {
        if ($user->id === Auth::id() || $user->isAdmin()) {
            return back()->with('error', 'Não é possível acessar contas administrativas como assinante.');
        }

        // Save original admin ID in session to allow returning
        session(['admin_impersonator_id' => Auth::id()]);
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "Acessando como {$user->name}. Use o topo para retornar.");
    }
}
