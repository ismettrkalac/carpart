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
        Schema::create('part_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku');
            $table->unsignedInteger('cost_cents')->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['part_id', 'supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_supplier');
    }
};
