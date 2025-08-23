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
        Schema::create('production', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('quantity_to_produce'); // Almacena la cantidad de producto a fabricar
            $table->decimal('price_for_product', 10, 3); // almacena el precio individual de cada profucto fabricado
            $table->decimal('total_cost', 10, 3); // Almacena el total de la produccion
            $table->date('production_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production');
    }
};
