<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('credit_card_id');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'posted', 'cancelled', 'reversed'])->default('pending');
            $table->unsignedInteger('installments_total')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['credit_card_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['credit_card_id', 'user_id'])->references(['id', 'user_id'])->on('credit_cards')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE credit_card_transactions ADD CONSTRAINT credit_card_transactions_positive_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_transactions');
    }
};
