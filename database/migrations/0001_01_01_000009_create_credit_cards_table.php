<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->string('last_digits', 4)->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->decimal('limit_amount', 15, 2)->unsigned()->nullable();
            $table->decimal('available_limit', 15, 2)->unsigned()->nullable();
            $table->unsignedTinyInteger('statement_day')->nullable();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->enum('status', ['active', 'blocked', 'closed', 'pending'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'user_id']);
            $table->unique(['user_id', 'issuer', 'last_digits']);
            $table->index(['user_id']);
            $table->index(['statement_day']);
            $table->index(['due_day']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE credit_cards ADD CONSTRAINT credit_cards_non_negative_limits CHECK (limit_amount >= 0 AND (available_limit IS NULL OR available_limit >= 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
