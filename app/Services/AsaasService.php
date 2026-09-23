<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    /**
     * Obtains the base URL depending on configured environment (sandbox or production).
     */
    public function getBaseUrl(): string
    {
        $env = SystemSetting::get('asaas_environment') ?: env('ASAAS_ENVIRONMENT', 'sandbox');
        return $env === 'production' 
            ? 'https://api.asaas.com/v3' 
            : 'https://sandbox.asaas.com/api/v3';
    }

    /**
     * Obtains the API Key from DB or .env.
     */
    public function getApiKey(): ?string
    {
        return SystemSetting::get('asaas_api_key') ?: env('ASAAS_API_KEY');
    }

    /**
     * Builds HTTP client with Asaas headers and local SSL bypass.
     */
    protected function client(?string $apiKey = null)
    {
        $key = $apiKey ?: $this->getApiKey();

        $client = Http::timeout(25)
            ->withHeaders([
                'access_token' => $key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'DeixaSobrar-SaaS/1.0',
            ]);

        if (app()->environment('local', 'testing') || config('app.debug')) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Tests connectivity and API Key validity against Asaas balance endpoint.
     */
    public function testConnection(?string $apiKey = null, ?string $environment = null): array
    {
        $key = $apiKey ?: $this->getApiKey();
        $env = $environment ?: (SystemSetting::get('asaas_environment') ?: env('ASAAS_ENVIRONMENT', 'sandbox'));

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'Nenhuma Chave de API (Access Token) do Asaas informada ou configurada.',
                'environment' => $env,
            ];
        }

        $baseUrl = $env === 'production' 
            ? 'https://api.asaas.com/v3' 
            : 'https://sandbox.asaas.com/api/v3';

        try {
            $client = Http::timeout(10)
                ->withHeaders([
                    'access_token' => $key,
                    'Content-Type' => 'application/json',
                ]);

            if (app()->environment('local', 'testing') || config('app.debug')) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get("{$baseUrl}/finance/balance");

            if ($response->successful()) {
                $balance = $response->json('totalBalance') ?? 0.00;
                $formatted = number_format((float)$balance, 2, ',', '.');
                return [
                    'success' => true,
                    'message' => "Conexão com Asaas ({$env}) realizada com sucesso! Saldo em conta: R$ {$formatted}.",
                    'environment' => $env,
                    'balance' => $balance,
                ];
            }

            $errorMsg = $response->json('errors.0.description') ?? $response->body();
            return [
                'success' => false,
                'message' => "Falha na validação da API Key Asaas: {$errorMsg}",
                'environment' => $env,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erro de conexão/timeout com a API do Asaas: ' . $e->getMessage(),
                'environment' => $env,
            ];
        }
    }

    /**
     * Finds or creates a customer in Asaas.
     */
    public function findOrCreateCustomer(User $user, array $customerData = []): ?string
    {
        if (!empty($user->asaas_customer_id)) {
            return $user->asaas_customer_id;
        }

        $cpfCnpj = preg_replace('/\D/', '', $customerData['cpf_cnpj'] ?? ($user->cpf_cnpj ?? ''));
        $phone = preg_replace('/\D/', '', $customerData['phone'] ?? ($user->phone ?? ''));

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'cpfCnpj' => $cpfCnpj ?: null,
            'mobilePhone' => $phone ?: null,
            'externalReference' => (string) $user->id,
            'notificationDisabled' => false,
        ];

        try {
            $response = $this->client()->post("{$this->getBaseUrl()}/customers", array_filter($payload));

            if ($response->successful()) {
                $customerId = $response->json('id');
                $user->update([
                    'asaas_customer_id' => $customerId,
                    'cpf_cnpj' => $cpfCnpj ?: $user->cpf_cnpj,
                    'phone' => $phone ?: $user->phone,
                ]);
                return $customerId;
            }

            Log::error('Asaas customer creation failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('Asaas customer creation exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Creates recurring subscription in Asaas.
     */
    public function createSubscription(
        User $user,
        string $planTier,
        string $billingType = 'PIX',
        array $creditCard = [],
        array $creditCardHolder = []
    ): array {
        $customerId = $this->findOrCreateCustomer($user);

        if (!$customerId) {
            return [
                'success' => false,
                'message' => 'Não foi possível cadastrar ou identificar o cliente no Asaas.',
            ];
        }

        // Determine pricing & cycle
        $isAnnual = $planTier === 'pro_anual';
        $cycle = $isAnnual ? 'ANNUALLY' : 'MONTHLY';
        $value = match ($planTier) {
            'pro_mensal' => 19.90,
            'pro_anual' => 179.90,
            'familia' => 29.90,
            default => 0.00,
        };

        if ($value <= 0) {
            return [
                'success' => false,
                'message' => 'Plano gratuito não requer assinatura de pagamento.',
            ];
        }

        $payload = [
            'customer' => $customerId,
            'billingType' => strtoupper($billingType),
            'value' => $value,
            'nextDueDate' => now()->format('Y-m-d'),
            'cycle' => $cycle,
            'description' => "Assinatura DeixaSobrar - Plano " . strtoupper(str_replace('_', ' ', $planTier)),
            'externalReference' => "sub_user_{$user->id}_{$planTier}",
        ];

        if ($billingType === 'CREDIT_CARD' && !empty($creditCard)) {
            $payload['creditCard'] = $creditCard;
            $payload['creditCardHolderInfo'] = $creditCardHolder;
        }

        try {
            $response = $this->client()->post("{$this->getBaseUrl()}/subscriptions", $payload);

            if (!$response->successful()) {
                $errorMsg = $response->json('errors.0.description') ?? 'Erro ao criar assinatura no Asaas.';
                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }

            $subData = $response->json();
            $asaasSubId = $subData['id'];

            // Fetch the generated payment for this subscription to get Pix / Invoice data
            $paymentInfo = $this->getFirstPaymentForSubscription($asaasSubId);

            $pixData = null;
            if ($billingType === 'PIX' && !empty($paymentInfo['id'])) {
                $pixData = $this->getPixQrCode($paymentInfo['id']);
            }

            // Record or update user subscription in DB
            $subscription = $user->subscriptions()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_tier' => $planTier,
                    'status' => 'trialing', // aguardando pagamento
                    'payment_method' => strtolower($billingType),
                    'asaas_subscription_id' => $asaasSubId,
                    'asaas_payment_id' => $paymentInfo['id'] ?? null,
                    'pix_qrcode' => $pixData['encodedImage'] ?? null,
                    'pix_payload' => $pixData['payload'] ?? null,
                    'pix_expiration' => !empty($pixData['expirationDate']) ? $pixData['expirationDate'] : now()->addDay(),
                    'invoice_url' => $paymentInfo['invoiceUrl'] ?? ($paymentInfo['bankSlipUrl'] ?? null),
                    'current_period_end' => $isAnnual ? now()->addYear() : now()->addMonth(),
                ]
            );

            return [
                'success' => true,
                'message' => 'Assinatura iniciada com sucesso!',
                'subscription_id' => $asaasSubId,
                'payment_id' => $paymentInfo['id'] ?? null,
                'pix_qrcode' => $subscription->pix_qrcode,
                'pix_payload' => $subscription->pix_payload,
                'invoice_url' => $subscription->invoice_url,
                'status' => $subscription->status,
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Exceção ao se comunicar com Asaas: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Gets the first pending/created payment for a subscription.
     */
    public function getFirstPaymentForSubscription(string $asaasSubId): ?array
    {
        try {
            $res = $this->client()->get("{$this->getBaseUrl()}/subscriptions/{$asaasSubId}/payments");
            if ($res->successful()) {
                $data = $res->json('data');
                return !empty($data[0]) ? $data[0] : null;
            }
        } catch (\Throwable $e) {
            Log::warning("Could not fetch payments for subscription {$asaasSubId}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Fetches Pix QR Code and Payload (copia e cola) for an Asaas payment.
     */
    public function getPixQrCode(string $paymentId): ?array
    {
        try {
            $res = $this->client()->get("{$this->getBaseUrl()}/payments/{$paymentId}/pixQrCode");
            if ($res->successful()) {
                return $res->json();
            }
        } catch (\Throwable $e) {
            Log::warning("Could not fetch Pix QR code for payment {$paymentId}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Cancels an active subscription in Asaas.
     */
    public function cancelSubscription(string $asaasSubId): bool
    {
        try {
            $res = $this->client()->delete("{$this->getBaseUrl()}/subscriptions/{$asaasSubId}");
            return $res->successful();
        } catch (\Throwable $e) {
            Log::error("Failed to cancel subscription {$asaasSubId}: " . $e->getMessage());
            return false;
        }
    }
}
