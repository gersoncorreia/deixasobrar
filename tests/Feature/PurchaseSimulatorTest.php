<?php

namespace Tests\Feature;

use App\Actions\Financial\SimulatePurchaseImpactAction;
use App\Enums\AccountType;
use App\Enums\CategoryGroupType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_impact_action_calculates_safe_verdict(): void
    {
        $action = new SimulatePurchaseImpactAction();

        // 10 days remaining, current ceiling 50/day (500 free), purchase 100
        $safeToSpend = [
            'daily_ceiling' => 50.00,
            'available_capital' => 500.00,
            'days_remaining' => 10,
            'safety_reserve' => 100.00,
        ];

        $result = $action->execute($safeToSpend, 100.00);

        $this->assertEquals('safe', $result['verdict']);
        $this->assertEquals(400.00, $result['new_available_capital']);
        $this->assertEquals(40.00, $result['new_daily_ceiling']);
        $this->assertEquals(-10.00, $result['daily_ceiling_delta']);
        $this->assertStringContainsString('Compra Tranquila', $result['verdict_label']);
    }

    public function test_purchase_impact_action_calculates_tight_verdict(): void
    {
        $action = new SimulatePurchaseImpactAction();

        // 10 days remaining, current ceiling 50/day, purchase 350
        // New ceiling: 150 / 10 = 15/day (< 25 threshold)
        $safeToSpend = [
            'daily_ceiling' => 50.00,
            'available_capital' => 500.00,
            'days_remaining' => 10,
            'safety_reserve' => 100.00,
        ];

        $result = $action->execute($safeToSpend, 350.00);

        $this->assertEquals('tight', $result['verdict']);
        $this->assertEquals(150.00, $result['new_available_capital']);
        $this->assertEquals(15.00, $result['new_daily_ceiling']);
        $this->assertStringContainsString('Apertado', $result['verdict_label']);
    }

    public function test_purchase_impact_action_calculates_warning_verdict_when_exceeding_free_capital(): void
    {
        $action = new SimulatePurchaseImpactAction();

        // Available free 200, purchase 250, reserve is 100 -> capital becomes -50 (within -100 reserve)
        $safeToSpend = [
            'daily_ceiling' => 20.00,
            'available_capital' => 200.00,
            'days_remaining' => 10,
            'safety_reserve' => 100.00,
        ];

        $result = $action->execute($safeToSpend, 250.00);

        $this->assertEquals('warning', $result['verdict']);
        $this->assertEquals(-50.00, $result['new_available_capital']);
        $this->assertEquals(0.00, $result['new_daily_ceiling']);
        $this->assertStringContainsString('Reserva', $result['verdict_label']);
    }

    public function test_purchase_impact_action_calculates_danger_verdict_when_exceeding_reserve(): void
    {
        $action = new SimulatePurchaseImpactAction();

        // Available free 200, reserve 100, purchase 400 -> capital becomes -200 (< -100 reserve)
        $safeToSpend = [
            'daily_ceiling' => 20.00,
            'available_capital' => 200.00,
            'days_remaining' => 10,
            'safety_reserve' => 100.00,
        ];

        $result = $action->execute($safeToSpend, 400.00);

        $this->assertEquals('danger', $result['verdict']);
        $this->assertEquals(-200.00, $result['new_available_capital']);
        $this->assertEquals(0.00, $result['new_daily_ceiling']);
        $this->assertStringContainsString('Crítica', $result['verdict_label']);
    }

    public function test_dashboard_provides_upcoming_bills_data(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Principal',
            'type' => AccountType::Checking,
            'current_balance' => 2000.00,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Internet Fibra',
            'group_type' => CategoryGroupType::FixedExpense,
            'budget_ceiling' => 150.00,
            'due_day' => 15,
        ]);

        // Bill paid
        Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => -150.00,
            'status' => 'confirmed',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
            'description' => 'Pagamento Internet',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('upcomingBills')
            ->has('safeToSpend')
            ->where('upcomingBills.0.name', 'Internet Fibra')
            ->where('upcomingBills.0.is_paid', true)
        );
    }
}
