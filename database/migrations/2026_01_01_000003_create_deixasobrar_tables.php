<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Usuários & Assinaturas
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_tier', 50)->default('free'); // free, pro_mensal, pro_anual, familia
            $table->string('status', 50)->default('active'); // active, trialing, past_due, canceled
            $table->timestamp('current_period_end')->nullable();
            $table->string('payment_method', 30)->default('pix');
            $table->timestamps();
        });

        // Contas e Instituições Bancárias
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('type', 30); // checking, savings, credit_card, cash
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        // Categorias com Teto de Gastos
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('group_type', 40); // fixed_expense, routine_variable, habits_lifestyle, debt_installment, income
            $table->string('icon', 50)->default('tag');
            $table->string('color_hex', 7)->default('#2563eb');
            $table->decimal('budget_ceiling', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'group_type']);
        });

        // Histórico de Importações de Extratos
        Schema::create('statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('file_name', 255);
            $table->string('detected_bank', 50)->default('generic');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('imported_records')->default(0);
            $table->unsignedInteger('skipped_records')->default(0);
            $table->string('status', 30)->default('completed');
            $table->timestamps();
        });

        // Lançamentos
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('statement_import_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('description', 255);
            $table->text('raw_statement_text')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->decimal('amount', 14, 2); // Negativo = Saída, Positivo = Entrada
            $table->string('type', 20); // income, expense, transfer
            $table->string('status', 20)->default('confirmed');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_leak')->default(false); // Raio-X de vazamentos (apostas/bets, tarifas, micro-pix)
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->unsignedSmallInteger('installment_total')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'document_number']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('statement_imports');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('subscriptions');
    }
};
