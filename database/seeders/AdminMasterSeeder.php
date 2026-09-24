<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'gersoncorreia12@gmail.com'],
            [
                'name' => 'Gerson Correia (Master)',
                'password' => Hash::make('Ger809514@12'),
                'is_admin' => true,
                'is_active' => true,
                'payday_day' => 5,
                'safety_reserve' => 0.00,
            ]
        );
    }
}
