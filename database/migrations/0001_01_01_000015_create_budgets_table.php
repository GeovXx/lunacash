<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('currency', 3)->default('BRL');
            $table->decimal('target_amount', 15, 2)->unsigned()->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE budgets ADD CONSTRAINT budgets_non_negative_target CHECK (target_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
