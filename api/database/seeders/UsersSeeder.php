<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ariel',
                'email' => 'ariel.s.alvarez@gmail.com',
                'password' => bcrypt('asecurepass'),
            ],
            [
                'name' => 'Julieta Canzio',
                'email' => 'julicanzio@hotmail.com',
                'password' => Hash::make('cassa2025'),
            ],
            [
                'name' => 'Cintia Berbel',
                'email' => 'cinberbel@gmail.com',
                'password' => Hash::make('cassa2025'),
            ],
            [
                'name' => 'Silvia Valacco',
                'email' => 'silvyvalacco@gmail.com',
                'password' => Hash::make('cassa2025'),
            ],
            [
                'name' => 'Giuliana Rossi',
                'email' => 'giulianarossi1996@gmail.com',
                'password' => Hash::make('cassa2025'),
            ],
            [
                'name' => 'Gina Sinigaglia',
                'email' => 'ginasinigaglia@cassagrupoinmobiliario.com',
                'password' => Hash::make('cassa2025'),
            ],
            [
                'name' => 'Rocio Raigada',
                'email' => 'roraiga@hotmail.com',
                'password' => Hash::make('cassa2025'),
            ],
        ];

        foreach ($users as $user) {
            // Verifica si el usuario ya existe por su correo electrónico
            $existingUser = DB::table('users')->where('email', $user['email'])->first();

            // Solo crea el usuario si no existe
            if (!$existingUser) {
                DB::table('users')->insert($user);
            }
        }

    // Asignar el rol 'administrador' al usuario con email 'ariel.s.alvarez@gmail.com'
    $ariel = \App\Models\User::where('email', 'ariel.s.alvarez@gmail.com')->first();
    if ($ariel && !$ariel->hasRole('administrador')) {
        $ariel->assignRole('administrador');
    }
    }
}
