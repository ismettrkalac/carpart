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
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // 'payment' or 'fulfillment' — see App\Enums\OrderStatusType.
            $table->string('status_type');
            $table->string('from_status')->nullable();
            $table->string('to_status');

            // 'staff', 'customer', or 'system'. actor_id is intentionally
            // NOT a foreign key: a staff actor refers to moonshine_users.id,
            // a customer actor refers to users.id — two different tables.
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
