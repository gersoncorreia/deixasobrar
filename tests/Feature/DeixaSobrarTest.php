<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeixaSobrarTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Landing')
            ->has('stats')
        );
    }

    public function test_dashboard_renders_with_safe_to_spend_and_accounts(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente Principal',
            'type' => \App\Enums\AccountType::Checking,
            'current_balance' => 3000.00,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('safeToSpend')
            ->has('accounts')
            ->has('transactions')
            ->has('leaksSummary')
        );
    }

    public function test_universal_statement_uploader_imports_bb_csv(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'BB Test Account',
            'type' => \App\Enums\AccountType::Checking,
            'current_balance' => 100.00,
        ]);

        $csvSample = "\"Data\",\"Lançamento\",\"Detalhes\",\"Nº documento\",\"Valor\",\"Tipo Lançamento\"\n"
            . "\"01/01/2026\",\"S A L D O   A N T E R I O R\",\"\",\"0000\",\"1.500,00\",\"Saldo\"\n"
            . "\"02/01/2026\",\"Pix - Recebido\",\"01/01 09:00 63868900000175 AJC GATEWAY\",\"10900436981791\",\"120,00\",\"Entrada\"\n"
            . "\"02/01/2026\",\"Compra com Cartão\",\"01/01 18:50 MERCALE\",\"218475\",\"-46,98\",\"Saída\"\n"
            . "\"02/01/2026\",\"Pix - Enviado\",\"01/01 23:41 KOREABET TECNOLOGIA E ADM\",\"10203\",\"-10,00\",\"Saída\"\n"
            . "\"02/01/2026\",\"Saldo do dia\",\"Saldo Final do Dia\",\"0000\",\"1.563,02\",\"Saldo\"\n";

        $file = UploadedFile::fake()->createWithContent('extrato.csv', $csvSample);

        $response = $this->actingAs($user)->post('/extratos/upload', [
            'statement_file' => $file,
            'account_id' => $account->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'detected_bank' => 'Banco do Brasil',
            'imported' => 3, // Only the 3 real transactions imported, SALDO lines completely ignored!
        ]);

        // Verify only the 3 real transactions were created in database
        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseMissing('transactions', [
            'description' => 'S A L D O   A N T E R I O R',
        ]);
        $this->assertDatabaseMissing('transactions', [
            'description' => 'Saldo do dia',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => -10.00,
            'is_leak' => true, // Koreabet detected as leak!
        ]);
    }

    public function test_uploader_handles_iso_8859_1_encoded_statement_and_renders_dashboard_without_utf8_error(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'BB ISO Account',
            'type' => \App\Enums\AccountType::Checking,
            'current_balance' => 500.00,
        ]);

        // Create genuine ISO-8859-1 content with special Portuguese characters (ç, ã, í, º)
        $isoContent = mb_convert_encoding(
            "\"Data\",\"Lançamento\",\"Detalhes\",\"N° documento\",\"Valor\",\"Tipo Lançamento\"\r\n"
            . "\"02/01/2026\",\"Compra com Cartão\",\"01/01 18:50 MERCALE\",\"218475\",\"-46,98\",\"Saída\"\r\n"
            . "\"02/01/2026\",\"Transferência PIX\",\"02/01 12:00 JOÃO DA SILVA\",\"10204\",\"150,00\",\"Entrada\"\r\n",
            'ISO-8859-1',
            'UTF-8'
        );

        // Verify the raw content is indeed NOT valid UTF-8
        $this->assertFalse(mb_check_encoding($isoContent, 'UTF-8'));

        $file = UploadedFile::fake()->createWithContent('extrato_iso8859_1.csv', $isoContent);

        // Upload statement
        $uploadResponse = $this->actingAs($user)->post('/extratos/upload', [
            'statement_file' => $file,
            'account_id' => $account->id,
        ], ['Accept' => 'application/json']);

        $uploadResponse->assertStatus(200);
        $uploadResponse->assertJson([
            'success' => true,
            'imported' => 2,
        ]);

        // Ensure database records are 100% valid UTF-8
        $txs = $user->transactions()->get();
        $this->assertCount(2, $txs);
        foreach ($txs as $tx) {
            $this->assertTrue(mb_check_encoding($tx->description, 'UTF-8'));
            $this->assertTrue(mb_check_encoding($tx->raw_statement_text, 'UTF-8'));
        }

        // Now test dashboard rendering: Inertia json_encode must succeed with 200 OK!
        $dashResponse = $this->actingAs($user)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('transactions.data', 2)
        );
    }

    public function test_batch_statement_uploader_processes_multiple_files_simultaneously(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Multi-Extratos',
            'type' => \App\Enums\AccountType::Checking,
            'current_balance' => 1000.00,
        ]);

        // File 1: Banco do Brasil format CSV
        $bbCsvContent = "\"Data\",\"Lançamento\",\"Detalhes\",\"N° documento\",\"Valor\",\"Tipo Lançamento\"\r\n"
            . "\"05/01/2026\",\"Compra com Cartão\",\"SUPERMERCADO DIA\",\"11001\",\"-120,50\",\"Saída\"\r\n"
            . "\"06/01/2026\",\"Depósito\",\"SALARIO EMPRESA\",\"11002\",\"3500,00\",\"Entrada\"\r\n";
        $file1 = UploadedFile::fake()->createWithContent('extrato_bb_janeiro.csv', $bbCsvContent);

        // File 2: Nubank format CSV
        $nuCsvContent = "date,title,amount\r\n"
            . "2026-01-08,Posto Gasolina,-150.00\r\n"
            . "2026-01-09,Padaria Central,-25.50\r\n";
        $file2 = UploadedFile::fake()->createWithContent('extrato_nubank_janeiro.csv', $nuCsvContent);

        // Upload both files together
        $response = $this->actingAs($user)->post('/extratos/upload', [
            'statement_files' => [$file1, $file2],
            'account_id' => $account->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_batch' => true,
            'successful_files' => 2,
            'failed_files' => 0,
            'imported' => 4,
            'skipped' => 0,
        ]);

        $this->assertEquals(4, $user->transactions()->count());
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Compra com Cartão - SUPERMERCADO DIA',
            'amount' => -120.50,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Padaria Central',
            'amount' => -25.50,
        ]);
    }

    public function test_batch_statement_uploader_is_fault_tolerant_and_imports_valid_files_even_if_one_fails(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Conta Tolerante',
            'type' => \App\Enums\AccountType::Checking,
            'current_balance' => 500.00,
        ]);

        // File 1: Valid Nubank statement
        $validCsv = "date,title,amount\r\n2026-01-10,Farmacia Popular,-65.00\r\n";
        $file1 = UploadedFile::fake()->createWithContent('valido.csv', $validCsv);

        // File 2: Corrupted / unrecognizable content
        $invalidContent = "BLAH BLAH RANDOM TEXT NO DATE NO VALUE NO HEADERS\r\nJUST GIBBERISH\r\n";
        $file2 = UploadedFile::fake()->createWithContent('corrompido.csv', $invalidContent);

        $response = $this->actingAs($user)->post('/extratos/upload', [
            'statement_files' => [$file1, $file2],
            'account_id' => $account->id,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_batch' => true,
            'successful_files' => 1,
            'failed_files' => 1,
            'imported' => 1,
        ]);

        $this->assertEquals(1, $user->transactions()->count());
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Farmacia Popular',
            'amount' => -65.00,
        ]);
    }
}


