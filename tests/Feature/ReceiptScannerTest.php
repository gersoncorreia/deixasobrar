<?php

namespace Tests\Feature;

use App\Actions\Receipts\ProcessReceiptScanAction;
use App\Actions\Receipts\ReconcileReceiptWithStatementAction;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\ReceiptItem;
use App\Models\ReceiptScan;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptScannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_scanner_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/scanner');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Scanner/Index')
            ->has('scans')
            ->has('accounts')
            ->has('categories')
        );
    }

    public function test_user_can_capture_receipt_image_and_process_ocr(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('cupom_mercado.jpg');

        $response = $this->actingAs($user)->postJson('/scanner/capture', [
            'image' => $file,
            'scan_type' => 'paper_ocr',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('receipt_scans', [
            'user_id' => $user->id,
            'match_status' => 'pending',
        ]);

        $scan = ReceiptScan::where('user_id', $user->id)->first();
        $this->assertGreaterThan(0, $scan->items()->count());
    }

    public function test_user_can_manually_confirm_scanned_receipt(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Nubank',
            'type' => AccountType::Checking,
            'current_balance' => 1000.00,
        ]);

        $scan = ReceiptScan::create([
            'user_id' => $user->id,
            'scan_type' => 'paper_ocr',
            'image_path' => '/storage/receipts/fake.jpg',
            'merchant_name' => 'Supermercado Central',
            'purchased_at' => now(),
            'total_amount' => 150.00,
            'match_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson("/scanner/{$scan->id}/confirm", [
            'account_id' => $account->id,
            'description' => 'Compras no Supermercado Central',
            'amount' => 150.00,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertEquals(850.00, (float) $account->fresh()->current_balance);
        $this->assertEquals('manual_created', $scan->fresh()->match_status);
        $this->assertNotNull($scan->fresh()->transaction_id);
    }

    public function test_anti_duplication_reconciles_receipt_with_statement_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente BB',
            'type' => AccountType::Checking,
            'current_balance' => 2000.00,
        ]);

        // Transaction from statement
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => -142.80,
            'type' => 'expense',
            'description' => 'DEB COMPRA CARTAO',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        // Scanned receipt with same amount
        $scan = ReceiptScan::create([
            'user_id' => $user->id,
            'scan_type' => 'paper_ocr',
            'image_path' => '/storage/receipts/fake.jpg',
            'merchant_name' => 'Supermercado Pão de Açúcar',
            'purchased_at' => Carbon::now(),
            'total_amount' => 142.80,
            'match_status' => 'pending',
        ]);

        $reconcileAction = new ReconcileReceiptWithStatementAction();
        $candidates = $reconcileAction->findCandidates($scan);

        $this->assertCount(1, $candidates);
        $this->assertEquals($transaction->id, $candidates->first()->id);

        // Perform reconcile
        $response = $this->actingAs($user)->postJson("/scanner/{$scan->id}/reconcile", [
            'transaction_id' => $transaction->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('matched', $scan->fresh()->match_status);
        $this->assertEquals($transaction->id, $scan->fresh()->transaction_id);
    }
}
