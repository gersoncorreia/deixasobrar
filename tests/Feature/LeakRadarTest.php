<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeakRadarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_leak_radar_screen_with_breakdown_and_simulation_data(): void
    {
        $user = User::factory()->create([
            'payday_day' => 10,
            'safety_reserve' => 300.00,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Nubank Principal',
            'type' => AccountType::Checking,
            'current_balance' => 2000.00,
        ]);

        $categoryBets = Category::create([
            'user_id' => $user->id,
            'name' => 'Apostas & Jogos',
            'group_type' => \App\Enums\CategoryGroupType::HabitsLifestyle,
            'color' => '#f43f5e',
        ]);

        $categoryDelivery = Category::create([
            'user_id' => $user->id,
            'name' => 'Delivery Rápido',
            'group_type' => \App\Enums\CategoryGroupType::RoutineVariable,
            'color' => '#f59e0b',
        ]);

        // Transactions marked as leaks
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $categoryBets->id,
            'amount' => -150.00,
            'description' => 'Betano Apostas',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'is_leak' => true,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $categoryDelivery->id,
            'amount' => -50.00,
            'description' => 'iFood Lanche Noite',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'is_leak' => true,
        ]);

        // Normal transaction (not a leak)
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => -80.00,
            'description' => 'Supermercado Mensal',
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'is_leak' => false,
        ]);

        $response = $this->actingAs($user)->get('/vazamentos');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Leaks/Index')
            ->has('summary', fn (Assert $summary) => $summary
                ->where('totalAmount', 200)
                ->where('count', 2)
                ->etc()
            )
            ->has('categoriesBreakdown', 2)
            ->has('categories', 2)
            ->has('transactions.data', 2)
        );
    }

    public function test_user_can_filter_leak_radar_by_status_category_and_date_range(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente',
            'type' => AccountType::Checking,
            'current_balance' => 1000.00,
        ]);

        $cat1 = Category::create([
            'user_id' => $user->id,
            'name' => 'Lazer',
            'group_type' => \App\Enums\CategoryGroupType::HabitsLifestyle,
        ]);

        $cat2 = Category::create([
            'user_id' => $user->id,
            'name' => 'Farmácia',
            'group_type' => \App\Enums\CategoryGroupType::RoutineVariable,
        ]);

        // Trx 1: Leak, Cat 1, 2026-05-10
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $cat1->id,
            'amount' => -25.00,
            'description' => 'Cinema',
            'transaction_date' => '2026-05-10',
            'type' => 'expense',
            'is_leak' => true,
        ]);

        // Trx 2: Non-leak, Cat 2, 2026-05-12
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $cat2->id,
            'amount' => -40.00,
            'description' => 'Remédios',
            'transaction_date' => '2026-05-12',
            'type' => 'expense',
            'is_leak' => false,
        ]);

        // 1. Filter by category (passes month=all to search across all dates)
        $this->actingAs($user)->get("/vazamentos?category_id={$cat1->id}&month=all")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1));

        // 2. Filter by status: inactive
        $this->actingAs($user)->get('/vazamentos?status=inactive&month=all')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1));

        // 3. Filter by status: all
        $this->actingAs($user)->get('/vazamentos?status=all&month=all')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 2));

        // 4. Filter by date range
        $this->actingAs($user)->get('/vazamentos?status=all&start_date=2026-05-11&end_date=2026-05-15')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1));

        // 5. Filter by specific month
        $this->actingAs($user)->get('/vazamentos?status=all&month=2026-05')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 2));
    }
}
