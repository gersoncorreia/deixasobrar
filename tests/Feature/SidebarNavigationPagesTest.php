<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SidebarNavigationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_sidebar_pages_render_successfully_with_inertia(): void
    {
        $user = User::factory()->create();

        // 1. Dashboard / Visão Geral
        $this->actingAs($user)->get('/dashboard')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        // 2. Contas & Bancos
        $this->actingAs($user)->get('/contas')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Accounts/Index'));

        // 3. Extratos & Upload
        $this->actingAs($user)->get('/extratos')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Statements/Index'));

        // 4. Lançamentos & Transações
        $this->actingAs($user)->get('/transacoes')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Transactions/Index'));

        // 5. Contas Blindadas & Orçamentos
        $this->actingAs($user)->get('/blindagem')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('FixedBills/Index'));

        // 6. Raio-X de Vazamentos & Simulador
        $this->actingAs($user)->get('/vazamentos')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Leaks/Index'));

        // 7. Central de Inteligência & Análises 360
        $this->actingAs($user)->get('/analises')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Analytics/Index'));
    }
}

