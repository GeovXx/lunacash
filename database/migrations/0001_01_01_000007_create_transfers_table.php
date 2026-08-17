<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('from_account_id');
            $table->uuid('to_account_id');
            $table->decimal('amount', 15, 2)->unsigned();
            $table->string('currency', 3)->default('BRL');
            $table->date('transfer_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['from_account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
            $table->foreign(['to_account_id', 'user_id'])->references(['id', 'user_id'])->on('accounts')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE transfers ADD CONSTRAINT transfers_from_to_different CHECK (from_account_id <> to_account_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
