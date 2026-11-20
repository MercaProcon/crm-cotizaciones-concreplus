<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the initial test users and assign their roles.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Admin Dirección', 'email' => 'admin@procon.com.mx', 'role' => 'admin'],
            ['name' => 'Neysi Coordinadora', 'email' => 'neysi@procon.com.mx', 'role' => 'coordinadora'],
            ['name' => 'Leny Vendedora', 'email' => 'leny@procon.com.mx', 'role' => 'vendedora'],
            ['name' => 'Mónica Vendedora', 'email' => 'monica@procon.com.mx', 'role' => 'vendedora'],
            ['name' => 'Elsy Vendedora', 'email' => 'elsy@procon.com.mx', 'role' => 'vendedora'],
            ['name' => 'Ventanilla Consulta', 'email' => 'ventanilla@procon.com.mx', 'role' => 'ventanilla'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
