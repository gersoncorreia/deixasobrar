<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        return Inertia::render('Subscription/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'cpf_cnpj' => $user->cpf_cnpj,
                'phone' => $user->phone,
            ],
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_tier' => $subscription->plan_tier instanceof SubscriptionPlan 
                    ? $subscription->plan_tier->value 
                    : ($subscription->plan_tier ?? 'free'),
                'plan_name' => $subscription->plan_tier instanceof SubscriptionPlan 
                    ? $subscription->plan_tier->title() 
                    : 'Gratuito',
                'status' => $subscription->status,
                'payment_method' => $subscription->payment_method,
                'current_period_end' => $subscription->current_period_end?->format('d/m/Y'),
                'pix_qrcode' => $subscription->pix_qrcode,
                'pix_payload' => $subscription->pix_payload,
                'pix_expiration' => $subscription->pix_expiration?->format('d/m/Y H:i'),
                'invoice_url' => $subscription->invoice_url,
                'is_active' => $subscription->isActive(),
            ] : null,
            'plans' => [
                [
                    'tier' => 'free',
                    'name' => 'Gratuito',
                    'price' => 'R$ 0',
                    'period' => 'para sempre',
                    'features' => [
                        'Registro de contas manuais',
                        'Cálculo do Teto Diário Seguro',
                        'Até 3 importações de extrato/mês',
                        'Suporte da comunidade',
                    ],
                ],
                [
                    'tier' => 'pro_mensal',
                    'name' => 'Pro Mensal',
                    'price' => 'R$ 19,90',
                    'period' => '/mês',
                    'popular' => true,
                    'features' => [
                        'Tudo do Gratuito',
                        'Scanner OCR com Inteligência Artificial',
                        'Importações de extratos ILIMITADAS (OFX / CSV)',
                        'Radar de Vazamentos e Raio-X de Gastos',
                        'Alertas e cálculo dinâmico de liberação de margem',
                        'Pagamento automático via Pix ou Cartão',
                    ],
                ],
                [
                    'tier' => 'pro_anual',
                    'name' => 'Pro Anual',
                    'price' => 'R$ 179,90',
                    'period' => '/ano (R$ 14,99/mês - 25% OFF)',
                    'popular' => false,
                    'badge' => 'Economize 25%',
                    'features' => [
                        'Todos os benefícios do Plano Pro',
                        'Economia garantida de R$ 58,90 no ano',
                        'Acesso antecipado a novos recursos de IA',
                        'Suporte prioritário',
                    ],
                ],
            ],
        ]);
    }

    public function checkout(Request $request, AsaasService $asaasService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan_tier' => ['required', 'string', 'in:pro_mensal,pro_anual,familia'],
            'billing_type' => ['required', 'string', 'in:PIX,CREDIT_CARD'],
            'cpf_cnpj' => ['required', 'string', 'min:11', 'max:20'],
            'phone' => ['required', 'string', 'min:10', 'max:25'],
            // Credit card optional fields
            'credit_card' => ['nullable', 'array'],
            'credit_card.holderName' => ['required_if:billing_type,CREDIT_CARD', 'nullable', 'string'],
            'credit_card.number' => ['required_if:billing_type,CREDIT_CARD', 'nullable', 'string'],
            'credit_card.expiryMonth' => ['required_if:billing_type,CREDIT_CARD', 'nullable', 'string'],
            'credit_card.expiryYear' => ['required_if:billing_type,CREDIT_CARD', 'nullable', 'string'],
            'credit_card.ccv' => ['required_if:billing_type,CREDIT_CARD', 'nullable', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        // Update user contact details
        $user->update([
            'cpf_cnpj' => preg_replace('/\D/', '', $validated['cpf_cnpj']),
            'phone' => preg_replace('/\D/', '', $validated['phone']),
        ]);

        $creditCardHolder = [];
        if ($validated['billing_type'] === 'CREDIT_CARD') {
            $creditCardHolder = [
                'name' => $user->name,
                'email' => $user->email,
                'cpfCnpj' => $user->cpf_cnpj,
                'mobilePhone' => $user->phone,
                'postalCode' => '01310-100', // Padrão
                'addressNumber' => '100',
            ];
        }

        $result = $asaasService->createSubscription(
            $user,
            $validated['plan_tier'],
            $validated['billing_type'],
            $validated['credit_card'] ?? [],
            $creditCardHolder
        );

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return response()->json($result, 422);
            }
            return back()->with('error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('subscription.index')->with('success', $result['message']);
    }

    public function checkStatus(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $sub = $user->activeSubscription;

        return response()->json([
            'status' => $sub?->status ?? 'none',
            'is_active' => $sub ? $sub->isActive() : false,
            'plan' => $sub?->plan_tier instanceof SubscriptionPlan ? $sub->plan_tier->value : ($sub?->plan_tier ?? 'free'),
        ]);
    }

    public function cancel(AsaasService $asaasService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $sub = $user->activeSubscription;

        if ($sub && $sub->asaas_subscription_id) {
            $asaasService->cancelSubscription($sub->asaas_subscription_id);
        }

        if ($sub) {
            $sub->update([
                'status' => 'canceled',
                'plan_tier' => SubscriptionPlan::Free,
            ]);
        }

        return redirect()->route('subscription.index')->with('success', 'Sua assinatura foi cancelada. Seu plano agora é Gratuito.');
    }
}
