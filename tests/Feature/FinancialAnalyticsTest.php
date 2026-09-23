<?php

namespace Tests\Feature;

use App\Enums\CategoryGroupType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Nubank Principal',
            'type' => 'checking',
            'current_balance' => 3500.00,
        ]);

        $catFixed = Category::create([
            'name' => 'Aluguel & Moradia',
            'user_id' => $this->user->id,
            'group_type' => CategoryGroupType::FixedExpense,
            'color_hex' => '#3b82f6',
        ]);

        $catMarket = Category::create([
            'name' => 'Supermercado',
            'user_id' => $this->user->id,
            'group_type' => CategoryGroupType::RoutineVariable,
            'color_hex' => '#10b981',
        ]);

        $thisMonth = Carbon::now()->format('Y-m');

        // Income
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'transaction_date' => "{$thisMonth}-05",
            'description' => 'Salário Mensal',
            'amount' => 5000.00,
            'type' => 'income',
            'status' => 'confirmed',
        ]);

        // Fixed Expense
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $catFixed->id,
            'transaction_date' => "{$thisMonth}-10",
            'description' => 'Pagamento Aluguel',
            'amount' => -1500.00,
            'type' => 'expense',
            'status' => 'confirmed',
        ]);

        // Routine Expense
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $catMarket->id,
            'transaction_date' => "{$thisMonth}-12",
            'description' => 'Supermercado Assaí',
            'amount' => -800.00,
            'type' => 'expense',
            'status' => 'confirmed',
        ]);

        // Leak Expense
        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'transaction_date' => "{$thisMonth}-15",
            'description' => 'Aposta Betano',
            'amount' => -120.00,
            'type' => 'expense',
            'is_leak' => true,
            'leak_reason' => 'Aposta / Cassino online',
            'status' => 'confirmed',
        ]);
    }

    public function test_financial_analytics_service_calculates_metrics_and_health_score()
    {
        /** @var FinancialAnalyticsService $service */
        $service = app(FinancialAnalyticsService::class);
        $data = $service->getAnalyticsData($this->user);

        $this->assertEquals(5000.00, $data['metrics']['income']);
        $this->assertEquals(2420.00, $data['metrics']['expenses']);
        $this->assertEquals(2580.00, $data['metrics']['net_savings']);
        $this->assertEquals(120.00, $data['metrics']['leaks']);
        $this->assertGreaterThan(0, $data['metrics']['savings_rate']);

        // Health Score
        $this->assertArrayHasKey('score', $data['health_score']);
        $this->assertGreaterThanOrEqual(60, $data['health_score']['score']);
        $this->assertNotEmpty($data['health_score']['advice']);

        // Six Months History
        $this->assertCount(6, $data['six_months_history']['labels']);
        $this->assertCount(6, $data['six_months_history']['income']);

        // Group Distribution
        $this->assertNotEmpty($data['group_distribution']);

        // Top Villains
        $this->assertNotEmpty($data['top_villains']);
        $this->assertEquals('Pagamento Aluguel', $data['top_villains'][0]['description']);
    }

    public function test_authenticated_user_can_access_analytics_page()
    {
        $response = $this->actingAs($this->user)->get('/analises');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Analytics/Index')
            ->has('analytics.metrics')
            ->has('analytics.health_score')
            ->has('analytics.six_months_history')
            ->has('analytics.basket_analytics')
        );
    }

    public function test_basket_analytics_calculates_ocr_products_and_categories()
    {
        $scan = \App\Models\ReceiptScan::create([
            'user_id' => $this->user->id,
            'scan_type' => 'paper_ocr',
            'merchant_name' => 'Supermercado Pão de Açúcar',
            'purchased_at' => Carbon::now(),
            'total_amount' => 150.00,
            'match_status' => 'pending',
        ]);

        \App\Models\ReceiptItem::create([
            'receipt_scan_id' => $scan->id,
            'item_name' => 'Arroz Tipo 1 5kg',
            'quantity' => 2,
            'unit_price' => 25.00,
            'total_price' => 50.00,
            'item_category' => 'alimentacao_essencial',
        ]);

        \App\Models\ReceiptItem::create([
            'receipt_scan_id' => $scan->id,
            'item_name' => 'Chocolate Barra 100g',
            'quantity' => 5,
            'unit_price' => 10.00,
            'total_price' => 50.00,
            'item_category' => 'superfluo',
        ]);

        /** @var FinancialAnalyticsService $service */
        $service = app(FinancialAnalyticsService::class);
        $data = $service->getAnalyticsData($this->user);

        $basket = $data['basket_analytics'];
        $this->assertEquals(1, $basket['total_scans']);
        $this->assertEquals(150.00, $basket['total_spent']);
        $this->assertEquals(7, $basket['total_items_count']);
        $this->assertCount(2, $basket['top_products']);
        $this->assertNotEmpty($basket['category_split']);
        $keys = array_column($basket['category_split'], 'key');
        $this->assertContains('alimentacao_essencial', $keys);
        $this->assertContains('superfluo', $keys);
    }
}
