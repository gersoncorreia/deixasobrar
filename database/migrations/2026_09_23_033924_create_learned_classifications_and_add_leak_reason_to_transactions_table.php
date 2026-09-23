<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add leak_reason to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('leak_reason')->nullable()->after('is_leak');
        });

        // Create learned classifications table for zero-cost caching
        Schema::create('learned_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('normalized_description')->index();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_name')->nullable();
            $table->boolean('is_leak')->default(false);
            $table->string('leak_reason')->nullable();
            $table->string('source')->default('ai'); // 'ai', 'user_manual', 'rule'
            $table->timestamps();

            $table->index(['user_id', 'normalized_description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learned_classifications');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('leak_reason');
        });
    }
};
