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
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipe')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('input_id')->constrained('input')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('quantity_required', 10,3);
            $table->string('unit_used',10);//g/ml/un de la categorya del input
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
