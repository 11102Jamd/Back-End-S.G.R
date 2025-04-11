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
        Schema::create('recipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ID_manufacturing')->constrained('manufacturing')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('ID_inputs')->constrained('inputs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->double('AmountSpent',10,3);
            $table->string('UnitMeasurement',10);
            $table->double('PriceQuantitySpent',10,3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe');
    }
};
