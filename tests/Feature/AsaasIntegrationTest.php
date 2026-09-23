<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AsaasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsaasIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_subscription_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/assinatura');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Subscription/Index')
            ->has('plans')
            ->has('user')
        );
    }

    public function test_admin_can_test_asaas_connection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Simula resposta da API Asaas balance
        Http::fake([
            'https://sandbox.asaas.com/api/v3/finance/balance' => Http::response([
                'totalBalance' => 1500.50,
            ], 200),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/configuracoes/test-asaas', [
            'api_key' => 'fake_asaas_token_123',
            'environment' => 'sandbox',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'environment' => 'sandbox',
        ]);
    }

    public function test_webhook_confirms_payment_and_activates_subscription(): void
    {
        $user = User::factory()->create([
            'asaas_customer_id' => 'cus_000005822',
        ]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_tier' => SubscriptionPlan::ProMonthly,
            'status' => 'trialing',
            'asaas_subscription_id' => 'sub_998877',
            'asaas_payment_id' => 'pay_112233',
        ]);

        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_112233',
                'subscription' => 'sub_998877',
                'customer' => 'cus_000005822',
                'value' => 19.90,
            ]
        ]);

        $response->assertStatus(200);
        $this->assertEquals('active', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->current_period_end);
    }

    public function test_webhook_marks_overdue_as_past_due(): void
    {
        $user = User::factory()->create();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_tier' => SubscriptionPlan::ProMonthly,
            'status' => 'active',
            'asaas_subscription_id' => 'sub_554433',
        ]);

        $response = $this->postJson('/api/webhooks/asaas', [
            'event' => 'PAYMENT_OVERDUE',
            'payment' => [
                'id' => 'pay_445566',
                'subscription' => 'sub_554433',
            ]
        ]);

        $response->assertStatus(200);
        $this->assertEquals('past_due', $sub->fresh()->status);
    }

    public function test_user_can_cancel_subscription(): void
    {
        $user = User::factory()->create();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_tier' => SubscriptionPlan::ProMonthly,
            'status' => 'active',
            'asaas_subscription_id' => 'sub_to_cancel',
        ]);

        $response = $this->actingAs($user)->post('/assinatura/cancelar');

        $response->assertRedirect(route('subscription.index'));
        $this->assertEquals('canceled', $sub->fresh()->status);
        $this->assertEquals('free', $sub->fresh()->plan_tier->value);
    }
}
