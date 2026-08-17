<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('target_date')->nullable();
            $table->decimal('current_amount', 15, 2)->unsigned()->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE financial_goals ADD CONSTRAINT financial_goals_non_negative_amounts CHECK (target_amount > 0 AND current_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
