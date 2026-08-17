<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('credit_card_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('closing_date');
            $table->date('due_date');
            $table->enum('status', ['open', 'closed', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('open');
            $table->decimal('minimum_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('total_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('paid_amount', 15, 2)->unsigned()->default(0);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['credit_card_id', 'period_start', 'period_end']);
            $table->index(['user_id']);
            $table->index(['credit_card_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['credit_card_id', 'user_id'])->references(['id', 'user_id'])->on('credit_cards')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE credit_card_invoices ADD CONSTRAINT credit_card_invoices_due_after_closing CHECK (due_date >= closing_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_invoices');
    }
};
