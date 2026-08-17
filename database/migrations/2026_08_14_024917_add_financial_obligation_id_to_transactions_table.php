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
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('financial_obligation_id')->nullable()->after('category_id');
            $table->foreign('financial_obligation_id')
                ->references('id')
                ->on('financial_obligations')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['financial_obligation_id']);
            $table->dropColumn('financial_obligation_id');
        });
    }
};
