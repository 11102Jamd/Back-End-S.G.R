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
        Schema::create('input_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('order')->onDelete('cascade');
            $table->foreignId('input_id')->constrained('input')->onDelete('cascade');
            $table->decimal('quantity_total', 10,3);
            $table->string('unit', 10);//lb,kg,g,ml,un,l
            $table->decimal('quantity_remaining', 10,3);
            $table->string('unit_converted', 10);// g,ml,un
            $table->decimal('unit_price', 10,3);
            $table->decimal('subtotal_price',10,3);
            $table->integer('batch_number');
            $table->date('received_date')->useCurrent();
            $table->timestamps();

            $table->index(['input_id', 'received_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('input_batches');
    }
};
