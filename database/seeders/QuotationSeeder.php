<?php

namespace Database\Seeders;

use App\Models\Quotation;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Seed ~20 fake quotations, including a few intentional phone duplicates
     * so the future duplicates module has something real to group.
     */
    public function run(): void
    {
        // Teléfonos ficticios que se repiten a propósito (2 cotizaciones cada uno).
        $duplicatePhones = [
            '5219994561234',
            '5219987654321',
            '5219991122334',
        ];

        foreach ($duplicatePhones as $phone) {
            Quotation::factory()
                ->count(2)
                ->withPhone($phone)
                ->create();
        }

        // Resto de cotizaciones con teléfonos únicos entre sí.
        Quotation::factory()->count(14)->create();
    }
}
