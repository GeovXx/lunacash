<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('transaction_id');
            $table->uuid('tag_id');
            $table->timestamps();

            $table->unique(['transaction_id', 'tag_id']);
            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['transaction_id', 'user_id'])->references(['id', 'user_id'])->on('transactions')->cascadeOnDelete();
            $table->foreign(['tag_id', 'user_id'])->references(['id', 'user_id'])->on('tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_tags');
    }
};
