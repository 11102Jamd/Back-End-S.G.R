<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name1' => 'Juan',
            'name2' => 'David',
            'surname1' => 'plazas',
            'surname2' => 'hernandez',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('juan1234'),
            'rol' => 'Cajero',
        ]);

        User::factory()->create([
            'name1' => 'Juan',
            'name2'=> 'Alejandro',
            'surname1' => 'Muñoz',
            'surname2' => 'Devia',
            'email' => 'juanmunoz@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('juanmunoz1234'),
            'rol' => 'Administrador',
        ]);
    }
}
