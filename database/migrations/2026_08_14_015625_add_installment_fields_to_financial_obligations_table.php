<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_obligations', function (Blueprint $table) {
            $table->uuid('installment_plan_id')->nullable()->after('account_id');
            $table->integer('installment_number')->nullable()->after('installment_plan_id');
            $table->uuid('category_id')->nullable()->after('installment_number');

            $table->foreign('installment_plan_id')->references('id')->on('installment_plans')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();

            $table->unique(['installment_plan_id', 'installment_number'], 'fin_obs_installment_plan_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('financial_obligations', function (Blueprint $table) {
            $table->dropForeign(['installment_plan_id']);
            $table->dropForeign(['category_id']);
            $table->dropUnique('fin_obs_installment_plan_number_unique');
            $table->dropColumn(['installment_plan_id', 'installment_number', 'category_id']);
        });
    }
};
