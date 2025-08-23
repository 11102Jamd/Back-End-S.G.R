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
        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('production')->onDelete('cascade');
            $table->foreignId('input_id')->constrained('input')->onDelete('cascade');
            $table->foreignId('input_batches_id')->constrained('input_batches')->onDelete('cascade');
            $table->decimal('quantity_used', 10,3); // Almacena la cantidad usada en gramos del inusmo
            $table->decimal('unit_price', 10,3); // almacena un precio extraño, se puede eliminar
            $table->decimal('total_cost', 10,3);
            $table->timestamps();

            $table->index(['production_id', 'input_batches_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_consumptions');
    }
};
