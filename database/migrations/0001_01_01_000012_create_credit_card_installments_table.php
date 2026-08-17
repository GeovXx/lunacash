<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('credit_card_transaction_id');
            $table->uuid('invoice_id')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['credit_card_transaction_id', 'sequence']);
            $table->index(['user_id']);
            $table->index(['credit_card_transaction_id']);
            $table->index(['invoice_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['credit_card_transaction_id', 'user_id'])->references(['id', 'user_id'])->on('credit_card_transactions')->cascadeOnDelete();
            $table->foreign(['invoice_id', 'user_id'])->references(['id', 'user_id'])->on('credit_card_invoices')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE credit_card_installments ADD CONSTRAINT credit_card_installments_positive_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_installments');
    }
};
