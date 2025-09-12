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
        Schema::table('input_batches', function (Blueprint $table) {
            //
            $table->string('original_unit')->nullable()->comment('Unidad ingresada por el usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('input_batches', function (Blueprint $table) {
            //
            $table->dropColumn('original_unit');
        });
    }
};
