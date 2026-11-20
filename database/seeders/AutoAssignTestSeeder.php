<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SOLO LOCAL — genera datos de prueba para el botón "Asignar
 * automáticamente pendientes", cubriendo los 5 casos de la prioridad por
 * historial de teléfono:
 *
 *  CASO 1 (TEST-AUTO-001..008): clientes nuevos de Yucatán, sin historial,
 *          teléfonos distintos — deben repartirse en ciclo puro.
 *  CASO 2 (TEST-AUTO-HIST-A + TEST-AUTO-009): TEST-AUTO-HIST-A ya está
 *          asignada a Monica Mukul (historial). TEST-AUTO-009 comparte su
 *          mismo teléfono y debe ir también a Monica, sin importar el ciclo.
 *  CASO 3 (TEST-AUTO-010 + TEST-AUTO-011): mismo teléfono entre sí, sin
 *          historial — deben quedar con la misma vendedora entre ellas.
 *  CASO 4 (TEST-AUTO-012 Campeche, TEST-AUTO-013 Quintana Roo): deben
 *          quedar pendientes, la automática no debe tocarlas.
 *  CASO 5 (TEST-AUTO-HIST-B + TEST-AUTO-014): TEST-AUTO-HIST-B está
 *          asignada a "Elsy Vendedora" (vendedora fuera del ciclo fijo,
 *          para no romper la validación de ciclo completo si se desactiva).
 *          TEST-AUTO-014 comparte su teléfono. Este caso requiere
 *          desactivar a Elsy manualmente antes de correr la automática
 *          sobre TEST-AUTO-014 — ver instrucciones de prueba.
 *
 * También verifica/crea las 4 vendedoras del ciclo si no existen en local.
 *
 * No se llama desde DatabaseSeeder (mismo patrón que FacadeRequestSeeder):
 * es exclusivamente manual, y NUNCA debe correrse en producción.
 *
 * Uso: php artisan db:seed --class=AutoAssignTestSeeder
 */
class AutoAssignTestSeeder extends Seeder
{
    private const VENDEDORAS = [
        ['name' => 'Leny Vazquez', 'email' => 'LVazquez@procon.com.mx'],
        ['name' => 'Alejandra Vela', 'email' => 'EVela@procon.com.mx'],
        ['name' => 'Monica Mukul', 'email' => 'MMukul@procon.com.mx'],
        ['name' => 'Guadalupe Novelo', 'email' => 'GNovelo@procon.com.mx'],
    ];

    public function run(): void
    {
        foreach (self::VENDEDORAS as $vendedora) {
            $user = User::firstOrCreate(
                ['email' => $vendedora['email']],
                [
                    'name' => $vendedora['name'],
                    'password' => Hash::make('Temporal2026*'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['vendedora']);

            if (! $user->is_active) {
                $user->update(['is_active' => true]);
            }
        }

        $baseTime = now()->subDay();
        $minute = 0;

        // CASO 1 — 8 clientes nuevos de Yucatán, teléfonos distintos.
        for ($i = 1; $i <= 8; $i++) {
            $this->quotation(sprintf('TEST-AUTO-%03d', $i), 'Yucatán', sprintf('99900000%02d', $i), $baseTime, ++$minute);
        }

        // CASO 2 — historial previo: TEST-AUTO-HIST-A ya asignada a Monica.
        $histA = $this->quotation('TEST-AUTO-HIST-A', 'Yucatán', '9991111111', $baseTime, ++$minute, Quotation::STATUS_ASIGNADA);
        $monica = User::where('email', 'MMukul@procon.com.mx')->first();
        $this->ensureCurrentAssignment($histA, $monica);

        // TEST-AUTO-009 comparte teléfono con TEST-AUTO-HIST-A: debe ir a Monica.
        $this->quotation('TEST-AUTO-009', 'Yucatán', '9991111111', $baseTime, ++$minute);

        // CASO 3 — dos pendientes nuevas, mismo teléfono entre sí, sin historial.
        $this->quotation('TEST-AUTO-010', 'Yucatán', '9993333333', $baseTime, ++$minute);
        $this->quotation('TEST-AUTO-011', 'Yucatán', '9993333333', $baseTime, ++$minute);

        // CASO 4 — otras zonas: deben quedar pendientes, sin tocar por la automática.
        $this->quotation('TEST-AUTO-012', 'Campeche', '9990000012', $baseTime, ++$minute);
        $this->quotation('TEST-AUTO-013', 'Quintana Roo', '9990000013', $baseTime, ++$minute);

        // CASO 5 — historial con vendedora fuera del ciclo fijo (Elsy), para
        // poder desactivarla sin romper la validación de ciclo completo.
        $elsy = User::firstOrCreate(
            ['email' => 'elsy@procon.com.mx'],
            [
                'name' => 'Elsy Vendedora',
                'password' => Hash::make('Temporal2026*'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $elsy->syncRoles(['vendedora']);
        if (! $elsy->is_active) {
            $elsy->update(['is_active' => true]);
        }

        $histB = $this->quotation('TEST-AUTO-HIST-B', 'Yucatán', '9992222222', $baseTime, ++$minute, Quotation::STATUS_ASIGNADA);
        $this->ensureCurrentAssignment($histB, $elsy);

        // TEST-AUTO-014 se crea aparte (no aquí) justo antes de correr la
        // prueba del CASO 5, para no mezclarla con el lote de los demás
        // casos — ver instrucciones de prueba en el chat.
    }

    private function quotation(string $folio, string $zone, string $phone, \Illuminate\Support\Carbon $baseTime, int $minuteOffset, string $status = Quotation::STATUS_NUEVA): Quotation
    {
        return Quotation::updateOrCreate(
            ['folio' => $folio],
            [
                'source_sheet' => null,
                'source_row' => null,
                'source_hash' => null,
                'imported_at' => null,
                'sheet_assigned_to' => null,
                'registered_at' => $baseTime->copy()->addMinutes($minuteOffset),
                'zone' => $zone,
                'client_name' => 'Cliente Prueba Auto '.$folio,
                'phone_raw' => $phone,
                'phone_normalized' => $phone,
                'description' => 'Cotización de prueba para asignación automática',
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

    private function ensureCurrentAssignment(Quotation $quotation, User $vendedora): void
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
            'assigned_by_user_id' => $vendedora->id,
            'assigned_to_user_id' => $vendedora->id,
            'assigned_at' => now()->subHours(2),
            'notes' => 'Historial de prueba (AutoAssignTestSeeder)',
            'is_current' => true,
        ]);
    }
}
