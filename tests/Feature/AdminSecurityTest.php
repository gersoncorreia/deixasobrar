<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_logging_in_at_standard_login_is_redirected_directly_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@teste.com',
            'password' => bcrypt('senha123'),
            'is_admin' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_regular_user_logging_in_at_standard_login_goes_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@teste.com',
            'password' => bcrypt('senha123'),
            'is_admin' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'cliente@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_portal_login_page_renders_successfully(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Login'));
    }

    public function test_regular_user_is_blocked_from_logging_in_at_admin_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'invasor@teste.com',
            'password' => bcrypt('senha123'),
            'is_admin' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'invasor@teste.com',
            'password' => 'senha123',
        ]);

        // Should return with error and user must NOT be authenticated
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_user_logs_in_successfully_via_admin_portal(): void
    {
        $admin = User::factory()->create([
            'email' => 'master@teste.com',
            'password' => bcrypt('senha123'),
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'master@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }
}
