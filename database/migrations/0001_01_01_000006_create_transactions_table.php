<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id');
            $table->uuid('category_id')->nullable();
            $table->enum('type', ['income', 'expense', 'adjustment', 'transfer', 'payment', 'refund']);
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('transaction_date');
            $table->timestamp('posted_at')->nullable();
            $table->enum('status', ['pending', 'posted', 'reconciled', 'cancelled'])->default('pending');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['account_id']);
            $table->index(['category_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        DB::statement('ALTER TABLE transactions ADD CONSTRAINT transactions_positive_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
