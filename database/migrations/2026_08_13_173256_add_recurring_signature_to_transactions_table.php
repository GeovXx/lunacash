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
            $table->uuid('recurring_profile_id')->nullable()->after('id');
            $table->date('recurring_occurrence_date')->nullable()->after('recurring_profile_id');
            
            $table->foreign('recurring_profile_id')->references('id')->on('recurring_profiles')->nullOnDelete();
            $table->unique(['recurring_profile_id', 'recurring_occurrence_date'], 'recurring_signature_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('recurring_signature_unique');
            $table->dropForeign(['recurring_profile_id']);
            $table->dropColumn(['recurring_profile_id', 'recurring_occurrence_date']);
        });
    }
};
