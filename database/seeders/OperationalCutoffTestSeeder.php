<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * SOLO LOCAL — genera cotizaciones de prueba en fechas 15, 16, 17, 18 y 19
 * de julio de 2026 (folios TEST-CUTOFF-*) para probar el corte operativo
 * (Quotation::OPERATIONAL_START_DATE = 2026-07-18). Cubre:
 *
 *  - Visibilidad simple: una cotización por cada una de las 5 fechas.
 *  - Duplicados solo entre antiguas (16A+16B, mismo teléfono, ambas antes
 *    del corte): no deben aparecer como duplicado una vez aplicado el corte.
 *  - Duplicado antigua+nueva (17A+18A, mismo teléfono): solo 18A es visible,
 *    no debe contar como duplicado (queda sola).
 *  - Duplicado nuevo+nuevo (18B+19A, mismo teléfono): ambas visibles, sí
 *    debe contar como duplicado.
 *  - Asignada antigua (17B) y asignada nueva (18C), ambas a la misma
 *    vendedora, para probar Dashboard/Mis cotizaciones.
 *
 * No se llama desde DatabaseSeeder (mismo patrón que FacadeRequestSeeder):
 * es exclusivamente manual, y NUNCA debe correrse en producción.
 *
 * Uso: php artisan db:seed --class=OperationalCutoffTestSeeder
 */
class OperationalCutoffTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->quotation('TEST-CUTOFF-15A', '2026-07-15 12:00:00', '9997770015');
        $this->quotation('TEST-CUTOFF-16A', '2026-07-16 12:00:00', '9997770016');
        $this->quotation('TEST-CUTOFF-16B', '2026-07-16 13:00:00', '9997770016');
        $this->quotation('TEST-CUTOFF-17A', '2026-07-17 12:00:00', '9997770017');
        $this->quotation('TEST-CUTOFF-18A', '2026-07-18 12:00:00', '9997770017');
        $this->quotation('TEST-CUTOFF-18B', '2026-07-18 13:00:00', '9997770018');
        $this->quotation('TEST-CUTOFF-19A', '2026-07-19 12:00:00', '9997770018');

        $leny = User::where('email', 'LVazquez@procon.com.mx')->first();
        $admin = User::role('admin')->first();

        if ($leny && $admin) {
            $old = $this->quotation('TEST-CUTOFF-17B', '2026-07-17 15:00:00', '9997770099', Quotation::STATUS_ASIGNADA);
            $this->ensureCurrentAssignment($old, $leny, $admin);

            $new = $this->quotation('TEST-CUTOFF-18C', '2026-07-18 15:00:00', '9997770098', Quotation::STATUS_ASIGNADA);
            $this->ensureCurrentAssignment($new, $leny, $admin);
        }
    }

    private function quotation(string $folio, string $registeredAt, string $phone, string $status = Quotation::STATUS_NUEVA): Quotation
    {
        return Quotation::updateOrCreate(
            ['folio' => $folio],
            [
                'source_sheet' => null,
                'source_row' => null,
                'source_hash' => null,
                'imported_at' => null,
                'sheet_assigned_to' => null,
                'registered_at' => $registeredAt,
                'zone' => 'Yucatán',
                'client_name' => 'Cliente Prueba Corte '.$folio,
                'phone_raw' => $phone,
                'phone_normalized' => $phone,
                'description' => 'Cotización de prueba para corte operativo 18/07/2026',
                'sheet_status' => 'Abierta',
                'location_link' => null,
                'quote_sent_status' => null,
                'quote_number' => null,
                'sale_closed' => null,
                'return_status' => null,
                'sheet_comments' => null,
                'extra_column_1' => null,
                'crm_status' => $status,
            ]
        );
    }

    private function ensureCurrentAssignment(Quotation $quotation, User $vendedora, User $assignedBy): void
    {
        $existing = $quotation->currentAssignment()->first();

        if ($existing && $existing->assigned_to_user_id === $vendedora->id) {
            return;
        }

        if ($existing) {
            $existing->update(['is_current' => false]);
        }

        Assignment::create([
            'quotation_id' => $quotation->id,
            'assigned_by_user_id' => $assignedBy->id,
            'assigned_to_user_id' => $vendedora->id,
            'assigned_at' => now()->subHours(3),
            'notes' => 'Historial de prueba (OperationalCutoffTestSeeder)',
            'is_current' => true,
        ]);
    }
}
