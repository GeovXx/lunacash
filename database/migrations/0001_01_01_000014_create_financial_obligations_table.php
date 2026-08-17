<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_obligations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id');
            $table->enum('direction', ['payable', 'receivable']);
            $table->string('title');
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('due_date');
            $table->date('issued_date')->nullable();
            $table->enum('status', ['open', 'paid', 'overdue', 'cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE financial_obligations ADD CONSTRAINT financial_obligations_positive_amount CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_obligations');
    }
};
