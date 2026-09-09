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
        Schema::table('orders', function (Blueprint $table) {
            // The Paysera *payment* id (distinct from payment_reference,
            // which holds the Paysera *order* id) — captured from the
            // webhook's settled payment once paid, and required to issue
            // a refund via Paysera's payment-executor API.
            $table->string('payment_id')->nullable()->unique()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_id');
        });
    }
};
