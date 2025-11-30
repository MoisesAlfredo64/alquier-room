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
        if (!Schema::hasColumn('clients', 'client_number')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('client_number')->unique()->nullable()->after('id');
            });

            // Generar client_number para clientes existentes
            DB::statement('SET @row_number = 0');
            DB::statement('UPDATE clients SET client_number = (@row_number:=@row_number + 1) ORDER BY id');
            
            // Hacer la columna NOT NULL después de poblarla
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('client_number')->unique()->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('client_number');
        });
    }
};
