<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id');
            $table->uuid('category_id');
            $table->enum('direction', ['payable', 'receivable']);
            $table->string('title');
            $table->decimal('total_amount', 15, 2)->unsigned();
            $table->integer('installments_count')->unsigned();
            $table->date('first_due_date');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly']);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['account_id']);
            $table->index(['category_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE installment_plans ADD CONSTRAINT installment_plans_positive_amount CHECK (total_amount > 0)');
        DB::statement('ALTER TABLE installment_plans ADD CONSTRAINT installment_plans_positive_count CHECK (installments_count >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
