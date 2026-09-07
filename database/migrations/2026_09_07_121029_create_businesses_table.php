<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
