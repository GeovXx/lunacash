<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->enum('type', ['expense', 'income', 'transfer', 'savings', 'investment', 'other'])->default('expense');
            $table->boolean('is_default')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'user_id']);
            $table->unique(['user_id', 'name']);
            $table->index(['user_id']);
            $table->index(['parent_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign(['parent_id', 'user_id'])->references(['id', 'user_id'])->on('categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
