<?php

namespace Tests\Feature;

use App\Actions\Financial\CalculateSafeToSpendAction;
use App\Enums\AccountType;
use App\Enums\CategoryGroupType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedBillsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_fixed_bills_list_and_summary_metrics(): void
    {
        $user = User::factory()->create();

        // Create user fixed category with ceiling
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Aluguel Apartamento',
            'group_type' => CategoryGroupType::FixedExpense,
            'budget_ceiling' => 1200.00,
            'due_day' => 10,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Principal',
            'type' => AccountType::Checking,
            'current_balance' => 2500.00,
        ]);

        // Transaction paying part of Aluguel in current month
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'description' => 'Pagamento Aluguel',
            'amount' => -800.00,
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user)->getJson('/categorias/contas-fixas');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'summary' => [
                'total_planned' => 1200.00,
                'total_paid' => 800.00,
                'total_pending' => 400.00,
            ],
        ]);

        $bills = collect($response->json('fixed_bills'));
        $aluguel = $bills->firstWhere('id', $category->id);
        $this->assertNotNull($aluguel);
        $this->assertEquals(1200.00, $aluguel['budget_ceiling']);
        $this->assertEquals(800.00, $aluguel['paid_amount']);
        $this->assertEquals(400.00, $aluguel['pending_amount']);
        $this->assertEquals(10, $aluguel['due_day']);
        $this->assertEquals('partial', $aluguel['status']);
    }

    public function test_user_can_create_custom_fixed_bill(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/categorias', [
            'name' => 'Academia Smart Fit',
            'group_type' => 'fixed_expense',
            'budget_ceiling' => 119.90,
            'due_day' => 15,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'category' => [
                'name' => 'Academia Smart Fit',
                'budget_ceiling' => '119.90',
                'due_day' => 15,
            ],
        ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Academia Smart Fit',
            'budget_ceiling' => 119.90,
            'due_day' => 15,
        ]);
    }

    public function test_user_can_set_budget_ceiling_and_due_day_on_fixed_bill(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Internet Fibra',
            'group_type' => CategoryGroupType::FixedExpense,
            'budget_ceiling' => 100.00,
            'due_day' => 5,
        ]);

        $response = $this->actingAs($user)->putJson("/categorias/{$category->id}", [
            'budget_ceiling' => 149.90,
            'due_day' => 8,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'budget_ceiling' => 149.90,
            'due_day' => 8,
        ]);
    }

    public function test_safe_to_spend_recalculates_dynamically_when_fixed_bill_is_paid(): void
    {
        $user = User::factory()->create([
            'payday_day' => 30,
            'safety_reserve' => 0.00,
        ]);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Salário',
            'type' => AccountType::Checking,
            'current_balance' => 3000.00,
        ]);

        $fixedCat = Category::create([
            'user_id' => $user->id,
            'name' => 'Aluguel Imóvel',
            'group_type' => CategoryGroupType::FixedExpense,
            'budget_ceiling' => 1500.00,
            'due_day' => 10,
        ]);

        $calculator = app(CalculateSafeToSpendAction::class);

        // Before payment: 3000 balance - 1500 pending = 1500 available capital
        $state1 = $calculator->execute($user, 30, 0.00);
        $this->assertEquals(1500.00, $state1['pending_fixed_bills']);
        $this->assertEquals(1500.00, $state1['available_capital']);

        // Now user pays Aluguel (-1500)
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $fixedCat->id,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'description' => 'Pagamento Aluguel Imóvel',
            'amount' => -1500.00,
            'type' => 'expense',
        ]);
        $account->update(['current_balance' => 1500.00]);

        // After payment: 1500 balance - 0 pending = 1500 available capital!
        // The pending fixed bill is no longer blocking funds because it is already paid!
        $state2 = $calculator->execute($user, 30, 0.00);
        $this->assertEquals(0.00, $state2['pending_fixed_bills']);
        $this->assertEquals(1500.00, $state2['available_capital']);
    }

    public function test_user_can_delete_custom_fixed_bill(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Conta Desnecessária',
            'group_type' => CategoryGroupType::FixedExpense,
            'budget_ceiling' => 50.00,
        ]);

        $response = $this->actingAs($user)->deleteJson("/categorias/{$category->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}
