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
        Schema::create('product_production', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('production')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product')->onDelete('cascade');
            $table->decimal('quantity_produced', 10, 3);
            $table->decimal('profit_margin_porcentage', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_production');
    }
};
