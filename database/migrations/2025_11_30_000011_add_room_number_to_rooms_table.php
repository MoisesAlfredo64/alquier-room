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
        if (!Schema::hasColumn('rooms', 'room_number')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('room_number')->unique()->nullable()->after('id');
            });

            // Generar room_number para habitaciones existentes (R-001, R-002, etc.)
            $rooms = DB::table('rooms')->orderBy('id')->get();
            foreach ($rooms as $index => $room) {
                DB::table('rooms')
                    ->where('id', $room->id)
                    ->update(['room_number' => 'R-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)]);
            }
            
            // Hacer la columna NOT NULL después de poblarla
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('room_number')->unique()->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('room_number');
        });
    }
};
