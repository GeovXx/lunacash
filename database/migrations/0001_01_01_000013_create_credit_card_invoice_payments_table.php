<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('invoice_id');
            $table->uuid('account_id');
            $table->uuid('transaction_id');
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('payment_date');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['invoice_id']);
            $table->index(['account_id']);
            $table->index(['transaction_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['invoice_id', 'user_id'])->references(['id', 'user_id'])->on('credit_card_invoices')->cascadeOnDelete();
            $table->foreign(['account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
            $table->foreign(['transaction_id', 'user_id'])->references(['id', 'user_id'])->on('transactions')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE credit_card_invoice_payments ADD CONSTRAINT credit_card_invoice_payments_positive_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_invoice_payments');
    }
};
