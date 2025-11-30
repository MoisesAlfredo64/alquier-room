<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rents', 'rent_number')) {
            Schema::table('rents', function (Blueprint $table) {
                $table->string('rent_number')->unique()->nullable()->after('id');
            });

            // Generar rent_number para alquileres existentes (ALQ-001, ALQ-002, etc.)
            $rents = DB::table('rents')->orderBy('id')->get();
            foreach ($rents as $index => $rent) {
                DB::table('rents')
                    ->where('id', $rent->id)
                    ->update(['rent_number' => 'ALQ-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)]);
            }
            
            // Hacer la columna NOT NULL después de poblarla
            Schema::table('rents', function (Blueprint $table) {
                $table->string('rent_number')->unique()->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn('rent_number');
        });
    }
};
