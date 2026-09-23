<?php

namespace Tests\Feature;

use App\Actions\Financial\ProcessUniversalStatementAction;
use App\Models\Account;
use App\Models\Category;
use App\Models\LearnedClassification;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiStatementClassificationTest extends TestCase
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
            'current_balance' => 1000.00,
        ]);

        Category::create(['name' => 'Tarifas Bancárias', 'user_id' => null, 'group_type' => \App\Enums\CategoryGroupType::FixedExpense, 'color_hex' => '#f43f5e']);
        Category::create(['name' => 'Lazer & Apostas', 'user_id' => null, 'group_type' => \App\Enums\CategoryGroupType::HabitsLifestyle, 'color_hex' => '#8b5cf6']);
        Category::create(['name' => 'Supermercado & Feira', 'user_id' => null, 'group_type' => \App\Enums\CategoryGroupType::RoutineVariable, 'color_hex' => '#10b981']);
    }

    public function test_learned_cache_classifies_statement_transaction_with_zero_ai_cost()
    {
        // Pre-seed learned cache for an ambiguous bank code
        LearnedClassification::create([
            'user_id' => $this->user->id,
            'normalized_description' => 'tar op pacote conta',
            'category_name' => 'Tarifas Bancárias',
            'is_leak' => true,
            'leak_reason' => 'Tarifa de pacote bancário evitável',
            'source' => 'ai',
        ]);

        // Mock HTTP so that if any external API call is made, it would fail
        Http::preventStrayRequests();

        $csvContent = "Data,Descricao,Valor\n2026-09-10,TAR OP PACOTE CONTA,-49.90\n";

        /** @var ProcessUniversalStatementAction $action */
        $action = app(ProcessUniversalStatementAction::class);
        $result = $action->execute($this->user, $this->account, $csvContent, 'extrato_teste.csv');

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);

        $tx = Transaction::where('user_id', $this->user->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('TAR OP PACOTE CONTA', $tx->description);
        $this->assertTrue($tx->is_leak);
        $this->assertEquals('Tarifa de pacote bancário evitável', $tx->leak_reason);
        $this->assertNotNull($tx->category_id);
    }

    public function test_ai_batch_enriches_unresolved_items_and_caches_for_future()
    {
        SystemSetting::set('system_gemini_api_key', 'mock_api_key');
        SystemSetting::set('enable_ai_statement_classification', '1');

        // Mock Gemini generateContent API response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        [
                                            'id' => 0,
                                            'categoria' => 'Lazer & Apostas',
                                            'is_leak' => true,
                                            'leak_reason' => 'Aposta online / Cassino',
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $csvContent = "Data,Descricao,Valor\n2026-09-15,PAG*CASINO_TIGER,-30.00\n";

        /** @var ProcessUniversalStatementAction $action */
        $action = app(ProcessUniversalStatementAction::class);
        $result = $action->execute($this->user, $this->account, $csvContent, 'extrato_ai.csv');

        $this->assertTrue($result['success']);

        $tx = Transaction::where('user_id', $this->user->id)->first();
        $this->assertNotNull($tx);
        $this->assertTrue($tx->is_leak);
        $this->assertEquals('Aposta online / Cassino', $tx->leak_reason);

        // Verify learned cache was populated
        $cached = LearnedClassification::where('user_id', $this->user->id)
            ->where('normalized_description', 'pag*casino_tiger')
            ->first();
        $this->assertNotNull($cached);
        $this->assertEquals('Lazer & Apostas', $cached->category_name);
        $this->assertTrue($cached->is_leak);
    }

    public function test_ai_enrichment_fails_gracefully_when_api_fails()
    {
        SystemSetting::set('system_gemini_api_key', 'mock_api_key');
        SystemSetting::set('enable_ai_statement_classification', '1');

        // Simulate 500 error from external API
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'API Timeout'], 500)
        ]);

        $csvContent = "Data,Descricao,Valor\n2026-09-15,DESCRICAO DESCONHECIDA,-15.00\n";

        /** @var ProcessUniversalStatementAction $action */
        $action = app(ProcessUniversalStatementAction::class);
        $result = $action->execute($this->user, $this->account, $csvContent, 'extrato_graceful.csv');

        // Import should not crash or break, transaction is still imported safely
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);

        $tx = Transaction::where('user_id', $this->user->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('DESCRICAO DESCONHECIDA', $tx->description);
    }
}
