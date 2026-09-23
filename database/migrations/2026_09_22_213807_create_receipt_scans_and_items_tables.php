<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tabela de Comprovantes e Notas Escaneadas
        Schema::create('receipt_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete(); // Vínculo após conciliação
            $table->string('scan_type', 30); // 'nfce_qrcode', 'paper_ocr', 'pix_receipt', 'manual_photo'
            $table->string('image_path', 255);
            $table->string('merchant_name', 150)->nullable();
            $table->string('merchant_tax_id', 20)->nullable(); // CNPJ
            $table->dateTime('purchased_at')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_method_detected', 50)->nullable(); // 'credit', 'debit', 'pix', 'cash'
            $table->string('card_last_digits', 4)->nullable();
            $table->json('raw_ocr_payload')->nullable(); // Resposta bruta da IA / SEFAZ
            $table->string('match_status', 30)->default('pending'); // 'pending', 'matched', 'manual_created', 'ignored'
            $table->timestamps();

            $table->index(['user_id', 'match_status']);
            $table->index(['user_id', 'purchased_at']);
        });

        // Tabela de Itens Detalhados do Cupom (Raio-X do Carrinho de Compras)
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_scan_id')->constrained()->onDelete('cascade');
            $table->string('item_name', 200);
            $table->decimal('quantity', 8, 3)->default(1.000);
            $table->string('unit', 10)->default('UN'); // UN, KG, LT
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('item_category', 50)->default('alimentacao_essencial'); // essencial, limpeza, supérfluo, bebidas, etc.
            $table->timestamps();

            $table->index(['receipt_scan_id', 'item_category']);
        });

        // Tabela de Despesas e Cobranças Recorrentes Detectadas
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 120); // Ex: 'Internet Vivo', 'Servidor Hostinger', 'Google AI'
            $table->decimal('expected_amount', 12, 2);
            $table->unsignedTinyInteger('due_day'); // Dia do vencimento (1 a 31)
            $table->string('frequency', 20)->default('monthly'); // monthly, annual, weekly
            $table->string('payment_channel', 50)->nullable(); // Cartão Nubank, Débito Sicredi, Boleto
            $table->boolean('is_active')->default(true);
            $table->date('last_detected_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'due_day']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('recurring_bills');
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipt_scans');
    }
};
