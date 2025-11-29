<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Moises',
            'email' => 'moises@gmail.com',
            'password' => Hash::make('moises123')
        ]);
    }

    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('waterprice');
        });
    }

    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('waterprice', 10, 2)->nullable();
        });
    }
}
