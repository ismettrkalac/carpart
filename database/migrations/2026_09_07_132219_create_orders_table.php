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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Public, unguessable identifier used in URLs (guest order access).
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('email');

            $table->string('shipping_name');
            $table->string('shipping_line1');
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_postal_code');
            $table->string('shipping_country');

            $table->string('billing_name');
            $table->string('billing_line1');
            $table->string('billing_line2')->nullable();
            $table->string('billing_city');
            $table->string('billing_state');
            $table->string('billing_postal_code');
            $table->string('billing_country');

            $table->string('payment_status')->default('pending_payment');
            $table->string('fulfillment_status')->default('unfulfilled');

            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('shipping_cents');
            $table->unsignedInteger('tax_cents');
            $table->unsignedInteger('total_cents');
            $table->string('currency', 3)->default('USD');

            // Idempotency key from the checkout form; a unique constraint
            // guarantees at most one order per checkout attempt even under
            // concurrent duplicate submissions.
            $table->string('idempotency_key')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
