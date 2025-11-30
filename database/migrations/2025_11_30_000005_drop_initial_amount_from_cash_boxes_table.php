<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_boxes', function (Blueprint $table) {
            if (Schema::hasColumn('cash_boxes', 'initial_amount')) {
                $table->dropColumn('initial_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_boxes', function (Blueprint $table) {
            $table->decimal('initial_amount', 10, 2)->default(0);
        });
    }
};
