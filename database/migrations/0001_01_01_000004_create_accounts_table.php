<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_type_id');
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->string('account_number')->nullable();
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['account_type_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('account_type_id')->references('id')->on('account_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
