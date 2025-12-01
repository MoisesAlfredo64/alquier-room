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
        Schema::table('rents', function (Blueprint $table) {
            $table->decimal('warranty_paid', 10, 2)->default(0)->after('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('warranty_amount', 10, 2)->default(0)->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn('warranty_paid');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('warranty_amount');
        });
    }
};
