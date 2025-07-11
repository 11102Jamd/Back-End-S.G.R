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
        Schema::create('order', function (Blueprint $table) {
            $table->id('ID_order');
            $table->foreignId('ID_user')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();;
            $table->dateTime('orderDate');
            $table->decimal('orderTotal', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.  
     */
    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
