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
        Schema::create('input_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ID_purchase_order')->constrained('purchase_order')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('ID_input')->constrained('inputs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('PriceQuantity',10,3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('input_order');
    }
};
