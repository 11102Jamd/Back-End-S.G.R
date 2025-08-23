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
        Schema::create('sale_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sale')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade');
            $table->decimal('quantity_requested', 10, 3);
            $table->decimal('subtotal_price', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_product');
    }
};
