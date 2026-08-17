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
        Schema::table('credit_card_invoices', function (Blueprint $table) {
            $table->unique(['id', 'user_id'], 'credit_card_invoices_id_user_id_unique');
        });

        Schema::table('credit_card_installments', function (Blueprint $table) {
            $table->unique(['id', 'user_id'], 'credit_card_installments_id_user_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_card_installments', function (Blueprint $table) {
            $table->dropUnique('credit_card_installments_id_user_id_unique');
        });

        Schema::table('credit_card_invoices', function (Blueprint $table) {
            $table->dropUnique('credit_card_invoices_id_user_id_unique');
        });
    }
};
