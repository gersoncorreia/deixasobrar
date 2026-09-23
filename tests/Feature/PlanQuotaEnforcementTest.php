<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Models\ReceiptScan;
use App\Models\StatementImport;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanQuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_is_blocked_after_reaching_ocr_limit(): void
    {
        Storage::fake('receipts');
        $user = User::factory()->create();

        // Limite padrão é 3. Criamos 3 scans para o usuário no mês atual.
        for ($i = 0; $i < 3; $i++) {
            ReceiptScan::create([
                'user_id' => $user->id,
                'image_path' => "receipts/test_{$i}.jpg",
                'scan_type' => 'paper_ocr',
                'merchant' => 'Supermercado Teste',
                'total_amount' => 50.00,
                'status' => 'pending',
            ]);
        }

        $file = UploadedFile::fake()->image('receipt_4.jpg', 600, 800);

        // Tenta enviar o 4º scan
        $response = $this->actingAs($user)->postJson('/scanner/capture', [
            'image' => $file,
            'scan_type' => 'paper_ocr',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'quota_exceeded' => true,
        ]);
    }

    public function test_pro_user_can_scan_beyond_free_limit(): void
    {
        Storage::fake('receipts');
        $user = User::factory()->create();

        // Assinatura Pro ativa
        Subscription::create([
            'user_id' => $user->id,
            'plan_tier' => SubscriptionPlan::ProMonthly,
            'status' => 'active',
        ]);

        // Simula 3 scans já feitos
        for ($i = 0; $i < 3; $i++) {
            ReceiptScan::create([
                'user_id' => $user->id,
                'image_path' => "receipts/test_{$i}.jpg",
                'scan_type' => 'paper_ocr',
                'merchant' => 'Supermercado Teste',
                'total_amount' => 50.00,
                'status' => 'pending',
            ]);
        }

        $file = UploadedFile::fake()->image('receipt_4.jpg', 600, 800);

        // Usuário Pro consegue realizar a 4ª leitura normalmente
        $response = $this->actingAs($user)->postJson('/scanner/capture', [
            'image' => $file,
            'scan_type' => 'paper_ocr',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_free_user_is_blocked_after_reaching_statement_import_limit(): void
    {
        Storage::fake('statements');
        $user = User::factory()->create();
        $account = $user->accounts()->create([
            'name' => 'Conta Teste',
            'type' => \App\Enums\AccountType::Checking,
        ]);

        // Cria 3 importações no mês atual
        for ($i = 0; $i < 3; $i++) {
            StatementImport::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'file_name' => "extrato_{$i}.csv",
                'file_hash' => md5("extrato_{$i}"),
                'total_records' => 10,
                'imported_records' => 10,
                'status' => 'completed',
            ]);
        }

        $csvContent = "Data,Lancamento,Valor\n01/01/2026,Compra Teste,-50.00\n";
        $file = UploadedFile::fake()->createWithContent('extrato_4.csv', $csvContent);

        // Tenta fazer a 4ª importação
        $response = $this->actingAs($user)->postJson('/extratos/upload', [
            'statement_files' => [$file],
            'account_id' => $account->id,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'quota_exceeded' => true,
        ]);
    }
}
