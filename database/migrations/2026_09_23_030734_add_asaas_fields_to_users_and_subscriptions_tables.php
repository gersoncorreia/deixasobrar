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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf_cnpj', 20)->nullable()->after('email');
            $table->string('phone', 25)->nullable()->after('cpf_cnpj');
            $table->string('asaas_customer_id', 50)->nullable()->after('phone');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('asaas_subscription_id', 50)->nullable()->after('payment_method');
            $table->string('asaas_payment_id', 50)->nullable()->after('asaas_subscription_id');
            $table->text('pix_qrcode')->nullable()->after('asaas_payment_id');
            $table->text('pix_payload')->nullable()->after('pix_qrcode');
            $table->timestamp('pix_expiration')->nullable()->after('pix_payload');
            $table->string('invoice_url', 255)->nullable()->after('pix_expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf_cnpj', 'phone', 'asaas_customer_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'asaas_subscription_id',
                'asaas_payment_id',
                'pix_qrcode',
                'pix_payload',
                'pix_expiration',
                'invoice_url',
            ]);
        });
    }
};
