<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryGroupType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_manual_expense_and_balance_decrements(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente',
            'type' => AccountType::Checking,
            'current_balance' => 1000.00,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Alimentação',
            'group_type' => CategoryGroupType::RoutineVariable,
        ]);

        $response = $this->actingAs($user)->post('/transacoes', [
            'description' => 'Almoço Restaurante',
            'amount' => 45.50,
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_date' => '2026-09-22',
            'is_leak' => true,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify transaction saved with negative amount for expense
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Almoço Restaurante',
            'amount' => -45.50,
            'is_leak' => true,
        ]);

        // Verify account balance updated (1000.00 - 45.50 = 954.50)
        $this->assertEquals(954.50, $account->fresh()->current_balance);
    }

    public function test_user_can_create_manual_income_and_balance_increments(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Digital',
            'type' => AccountType::Checking,
            'current_balance' => 500.00,
        ]);

        $response = $this->actingAs($user)->post('/transacoes', [
            'description' => 'Pix Recebido Freelance',
            'amount' => 350.00,
            'type' => 'income',
            'account_id' => $account->id,
            'transaction_date' => '2026-09-22',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 350.00,
            'type' => 'income',
        ]);

        $this->assertEquals(850.00, $account->fresh()->current_balance);
    }

    public function test_user_can_update_transaction_category_and_toggle_leak(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta',
            'type' => AccountType::Checking,
            'current_balance' => 100.00,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Lazer',
            'group_type' => CategoryGroupType::HabitsLifestyle,
        ]);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Cinema',
            'amount' => -30.00,
            'type' => TransactionType::Expense,
            'transaction_date' => '2026-09-22',
            'is_leak' => false,
        ]);

        $response = $this->actingAs($user)->put("/transacoes/{$tx->id}", [
            'category_id' => $category->id,
            'is_leak' => true,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals($category->id, $tx->fresh()->category_id);
        $this->assertTrue($tx->fresh()->is_leak);
    }

    public function test_user_can_delete_transaction_and_account_balance_reverts(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente',
            'type' => AccountType::Checking,
            'current_balance' => 900.00,
        ]);

        $tx = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Compra Errada',
            'amount' => -100.00,
            'type' => TransactionType::Expense,
            'transaction_date' => '2026-09-22',
        ]);

        $response = $this->actingAs($user)->delete("/transacoes/{$tx->id}", [], ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);
        // 900 - (-100) = 1000
        $this->assertEquals(1000.00, $account->fresh()->current_balance);
    }

    public function test_dashboard_filters_transactions_correctly(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Teste',
            'type' => AccountType::Checking,
            'current_balance' => 1000.00,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Mercado Pão de Açúcar',
            'amount' => -120.00,
            'type' => TransactionType::Expense,
            'transaction_date' => '2026-09-10',
            'is_leak' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'description' => 'Aposta BetPix',
            'amount' => -25.00,
            'type' => TransactionType::Expense,
            'transaction_date' => '2026-09-15',
            'is_leak' => true,
        ]);

        // Filter for leaks only
        $response = $this->actingAs($user)->get('/dashboard?type=leak');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Aposta BetPix')
        );

        // Filter by search query
        $searchResponse = $this->actingAs($user)->get('/dashboard?search=Açúcar');
        $searchResponse->assertStatus(200);
        $searchResponse->assertInertia(fn (Assert $page) => $page
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'Mercado Pão de Açúcar')
        );
    }

    public function test_user_can_update_cycle_preferences_and_teto_recalculates(): void
    {
        $user = User::factory()->create([
            'payday_day' => 5,
            'safety_reserve' => 200.00,
        ]);

        $response = $this->actingAs($user)->post('/configuracoes/ciclo', [
            'payday_day' => 20,
            'safety_reserve' => 500.00,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals(20, $user->fresh()->payday_day);
        $this->assertEquals(500.00, $user->fresh()->safety_reserve);

        // Check that Dashboard loads with new preferences
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertInertia(fn (Assert $page) => $page
            ->where('user.payday_day', 20)
            ->where('user.safety_reserve', 500)
            ->where('safeToSpend.next_payday_day', 20)
            ->where('safeToSpend.safety_reserve', 500)
        );
    }
}
