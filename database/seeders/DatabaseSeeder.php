<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Fase 1 (Concreplus): solo roles/permisos. UserSeeder y
        // QuotationSeeder traían usuarios reales de PROCON y datos de
        // ejemplo con zonas de PROCON — no aplican aquí todavía. Se crea un
        // admin de prueba propio por separado, y los datos reales de
        // Concreplus llegarán en la fase del importador de Google Sheets.
        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
