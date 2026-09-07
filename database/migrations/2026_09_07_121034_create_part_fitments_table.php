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
        Schema::create('part_fitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained()->cascadeOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year_start');
            $table->unsignedSmallInteger('year_end');
            $table->string('engine')->nullable();
            $table->string('trim')->nullable();
            $table->timestamps();

            $table->index(['make', 'model', 'year_start', 'year_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_fitments');
    }
};
