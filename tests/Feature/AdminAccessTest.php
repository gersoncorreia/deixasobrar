<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Should be redirected away from admin
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('metrics')
            ->has('recentUsers')
        );
    }

    public function test_admin_can_view_users_list_and_filter(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $subscriber = User::factory()->create([
            'name' => 'Cliente Teste VIP',
            'email' => 'vip@cliente.com',
            'is_admin' => false,
        ]);

        $subscriber->subscriptions()->create([
            'plan_tier' => 'pro_mensal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/usuarios?search=VIP');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data')
            ->where('users.data.0.email', 'vip@cliente.com')
        );
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post("/admin/usuarios/{$user->id}/toggle-status");

        $response->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_admin_can_update_user_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/usuarios/{$user->id}/update-plan", [
            'plan_tier' => 'pro_anual',
            'status' => 'active',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('pro_anual', $user->fresh()->activeSubscription->plan_tier->value);
    }

    public function test_admin_can_update_global_system_settings(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/configuracoes', [
            'settings' => [
                'landing_stat_users' => '25.000+',
                'landing_headline' => 'Novo Título Customizado pelo Admin Master',
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('25.000+', SystemSetting::get('landing_stat_users'));
        $this->assertEquals('Novo Título Customizado pelo Admin Master', SystemSetting::get('landing_headline'));
    }

    public function test_admin_can_impersonate_user_and_return(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $client = User::factory()->create([
            'name' => 'Cliente Alvo',
            'is_admin' => false,
        ]);

        // Impersonate
        $response = $this->actingAs($admin)->post("/admin/usuarios/{$client->id}/impersonate");

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($client->id, auth()->id());
        $this->assertEquals($admin->id, session('admin_impersonator_id'));

        // Stop impersonation
        $stopResponse = $this->get('/admin/stop-impersonation');
        $stopResponse->assertRedirect(route('admin.users.index'));
        $this->assertEquals($admin->id, auth()->id());
    }

    public function test_admin_can_trigger_ai_connection_test(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/configuracoes/test-ai', [
            'api_key' => 'fake_test_key_123',
            'model' => 'gemini-1.5-flash',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'model',
        ]);
    }
}
