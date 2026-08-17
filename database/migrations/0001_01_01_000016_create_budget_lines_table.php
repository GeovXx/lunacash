<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('budget_id');
            $table->uuid('category_id')->nullable();
            $table->string('name');
            $table->decimal('planned_amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['budget_id']);
            $table->index(['category_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['budget_id', 'user_id'])->references(['id', 'user_id'])->on('budgets')->cascadeOnDelete();
            $table->foreign(['category_id', 'user_id'])->references(['id', 'user_id'])->on('categories')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE budget_lines ADD CONSTRAINT budget_lines_non_negative_planned CHECK (planned_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
