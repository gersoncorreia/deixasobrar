<?php

namespace Tests\Feature;

use App\Actions\Financial\CalculateSafeToSpendAction;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_accounts_with_balances(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => 'Nubank Principal',
            'type' => AccountType::Checking,
            'current_balance' => 1500.00,
            'bank_name' => 'Nubank',
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => 'Poupança Caixa',
            'type' => AccountType::Savings,
            'current_balance' => 3000.00,
            'bank_name' => 'Caixa',
        ]);

        $response = $this->actingAs($user)->getJson('/contas');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total_balance' => 4500.00,
        ]);
        $this->assertCount(2, $response->json('accounts'));
    }

    public function test_user_can_create_new_bank_account_and_safe_to_spend_recalculates(): void
    {
        $user = User::factory()->create([
            'payday_day' => 30,
            'safety_reserve' => 0.00,
        ]);

        $calculator = app(CalculateSafeToSpendAction::class);
        $stateBefore = $calculator->execute($user, 30, 0.00);
        $this->assertEquals(0.00, $stateBefore['current_balance']);

        $response = $this->actingAs($user)->postJson('/contas', [
            'name' => 'Inter Corrente',
            'type' => 'checking',
            'bank_name' => 'Inter',
            'current_balance' => 2000.00,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Inter Corrente',
            'current_balance' => 2000.00,
            'bank_name' => 'Inter',
        ]);

        $stateAfter = $calculator->execute($user, 30, 0.00);
        $this->assertEquals(2000.00, $stateAfter['current_balance']);
        $this->assertEquals(2000.00, $stateAfter['available_capital']);
    }

    public function test_user_can_update_account_name_and_reconcile_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Itaú Antigo',
            'type' => AccountType::Checking,
            'current_balance' => 800.00,
        ]);

        $response = $this->actingAs($user)->putJson("/contas/{$account->id}", [
            'name' => 'Itaú Salário Atualizado',
            'type' => 'checking',
            'current_balance' => 1250.50, // Reconciliação direta
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Itaú Salário Atualizado',
            'current_balance' => 1250.50,
        ]);
    }

    public function test_user_cannot_access_or_modify_other_users_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $accountUser2 = Account::create([
            'user_id' => $user2->id,
            'name' => 'Conta Privada do User 2',
            'type' => AccountType::Checking,
            'current_balance' => 9999.00,
        ]);

        $response = $this->actingAs($user1)->putJson("/contas/{$accountUser2->id}", [
            'name' => 'Tentativa de Hack',
            'current_balance' => 0.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_account_and_balance_decrements(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta a Deletar',
            'type' => AccountType::Cash,
            'current_balance' => 350.00,
        ]);

        $response = $this->actingAs($user)->deleteJson("/contas/{$account->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }
}
