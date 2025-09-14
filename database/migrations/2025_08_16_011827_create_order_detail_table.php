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
        Schema::create('orderDetail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ID_order')->constrained('order')->cascadeOnDelete()->cascadeOnUpdate();;
            $table->foreignId('ID_product')->constrained('product')->cascadeOnDelete()->cascadeOnUpdate();;
            $table->decimal('requestedQuantity',10,2);
            $table->double('priceQuantity', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orderDetail');
    }
};
