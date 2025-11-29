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
        Schema::create('electricity_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('rent_id')->constrained('rents')->onDelete('cascade');
            $table->date('reading_date');
            $table->decimal('initial_reading', 10, 2);
            $table->decimal('final_reading', 10, 2);
            $table->decimal('consumption', 10, 2);
            $table->decimal('kwh_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_readings');
    }
};
