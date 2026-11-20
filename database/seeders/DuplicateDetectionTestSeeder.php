<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\FollowUp;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SOLO LOCAL — genera cotizaciones de prueba (folios TEST-DUP-*) para
 * validar la asignación automática por grupo de teléfono con detección de
 * posible duplicado real y conflicto de vendedora. Cubre:
 *
 *  CASO 1 (TEST-DUP-001, 002): mismo teléfono, sin historial, solicitudes
 *          distintas — ambas deben ir a la MISMA vendedora, un solo turno
 *          de ciclo consumido.
 *  CASO 2 (TEST-DUP-HIST-A + TEST-DUP-003): historial con Monica Mukul,
 *          solicitud nueva distinta — debe ir también a Monica.
 *  CASO 3 (TEST-DUP-004, 005): solicitudes casi idénticas (mismas palabras,
 *          distinto orden), fecha cercana, ninguna con seguimiento — solo
 *          la más antigua (004) se asigna; 005 queda 'nueva' como posible
 *          duplicado real.
 *  CASO 3b (TEST-DUP-006, 007): igual que 3, pero 007 (la más nueva) ya
 *          tiene un FollowUp — debe ser la principal, no la más antigua.
 *  CASO 4 (TEST-DUP-HIST-B1 a Leny, TEST-DUP-HIST-B2 a Elsy, + TEST-DUP-008):
 *          historial repartido entre 2 vendedoras distintas — la pendiente
 *          se omite por conflicto, no se asigna a nadie.
 *  CASO 5 (TEST-DUP-009, 010): teléfonos distintos, sin relación — flujo
 *          normal de ciclo.
 *  CASO 6 (TEST-DUP-011): zona Campeche — la automática no debe tocarla.
 *  CASO 7 (TEST-DUP-HIST-C a Elsy, + TEST-DUP-012): historial con una sola
 *          vendedora (Elsy), pero se desactiva para la prueba — debe caer
 *          al ciclo con la nota existente, no a Elsy.
 *
 * No se llama desde DatabaseSeeder: exclusivamente manual, y NUNCA debe
 * correrse en producción.
 *
 * Uso: php artisan db:seed --class=DuplicateDetectionTestSeeder
 */
class DuplicateDetectionTestSeeder extends Seeder
{
    public function run(): void
    {
        $leny = User::where('email', 'LVazquez@procon.com.mx')->first();
        $monica = User::where('email', 'MMukul@procon.com.mx')->first();
        $admin = User::role('admin')->first();

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

        $base = now()->subDays(2);

        // CASO 1
        $this->quotation('TEST-DUP-001', $base->copy()->addHours(1), '9995550001', 'Cemento gris 50kg y varilla 3/8');
        $this->quotation('TEST-DUP-002', $base->copy()->addHours(2), '9995550001', 'Lámina galvanizada y clavos');

        // CASO 2
        $histA = $this->quotation('TEST-DUP-HIST-A', $base->copy()->subDays(10), '9995550002', 'Cotización previa cliente', Quotation::STATUS_ASIGNADA);
        $this->ensureCurrentAssignment($histA, $monica, $admin);
        $this->quotation('TEST-DUP-003', $base->copy()->addHours(3), '9995550002', 'Tinaco de 1100 litros');

        // CASO 3 — mismas palabras, orden distinto, 1 día de diferencia
        $this->quotation('TEST-DUP-004', $base->copy()->addHours(4), '9995550003', 'Block de 12, polvo, grava y cal');
        $this->quotation('TEST-DUP-005', $base->copy()->addHours(4)->addDay(), '9995550003', 'Polvo, grava, cal y block de 12');

        // CASO 3b — igual, pero la mas nueva ya tiene seguimiento
        $this->quotation('TEST-DUP-006', $base->copy()->addHours(5), '9995550004', 'Arena, grava y cemento para losa');
        $newerWithFollowUp = $this->quotation('TEST-DUP-007', $base->copy()->addHours(5)->addDay(), '9995550004', 'Grava, arena y cemento para losa');
        FollowUp::create([
            'quotation_id' => $newerWithFollowUp->id,
            'user_id' => $admin->id,
            'status' => Quotation::STATUS_EN_SEGUIMIENTO,
            'comment' => 'Seguimiento de prueba (DuplicateDetectionTestSeeder)',
        ]);

        // CASO 4 — historial repartido entre 2 vendedoras distintas
        $histB1 = $this->quotation('TEST-DUP-HIST-B1', $base->copy()->subDays(20), '9995550005', 'Cotización previa A', Quotation::STATUS_ASIGNADA);
        $this->ensureCurrentAssignment($histB1, $leny, $admin);
        $histB2 = $this->quotation('TEST-DUP-HIST-B2', $base->copy()->subDays(15), '9995550005', 'Cotización previa B', Quotation::STATUS_ASIGNADA);
        $this->ensureCurrentAssignment($histB2, $elsy, $admin);
        $this->quotation('TEST-DUP-008', $base->copy()->addHours(6), '9995550005', 'Pintura vinílica y sellador');

        // CASO 5 — sin relación entre sí
        $this->quotation('TEST-DUP-009', $base->copy()->addHours(7), '9995550006', 'Tubería PVC para drenaje');
        $this->quotation('TEST-DUP-010', $base->copy()->addHours(8), '9995550007', 'Malla electrosoldada y alambre recocido');

        // CASO 6 — otra zona
        Quotation::updateOrCreate(
            ['folio' => 'TEST-DUP-011'],
            $this->baseAttributes($base->copy()->addHours(9), '9995550008', 'Solicitud de prueba fuera de Yucatán', Quotation::STATUS_NUEVA, 'Campeche')
        );

        // CASO 7 — historial con una sola vendedora (Elsy), se desactiva en la prueba
        $histC = $this->quotation('TEST-DUP-HIST-C', $base->copy()->subDays(5), '9995550009', 'Cotización previa C', Quotation::STATUS_ASIGNADA);
        $this->ensureCurrentAssignment($histC, $elsy, $admin);
        $this->quotation('TEST-DUP-012', $base->copy()->addHours(10), '9995550009', 'Solicitud nueva del mismo cliente');
    }

    private function quotation(string $folio, \Illuminate\Support\Carbon $registeredAt, string $phone, string $description, string $status = Quotation::STATUS_NUEVA): Quotation
    {
        return Quotation::updateOrCreate(
            ['folio' => $folio],
            $this->baseAttributes($registeredAt, $phone, $description, $status, 'Yucatán')
        );
    }

    private function baseAttributes(\Illuminate\Support\Carbon $registeredAt, string $phone, string $description, string $status, string $zone): array
    {
        return [
            'source_sheet' => null,
            'source_row' => null,
            'source_hash' => null,
            'imported_at' => null,
            'sheet_assigned_to' => null,
            'registered_at' => $registeredAt,
            'zone' => $zone,
            'client_name' => 'Cliente Prueba Dup '.$phone,
            'phone_raw' => $phone,
            'phone_normalized' => $phone,
            'description' => $description,
            'sheet_status' => 'Abierta',
            'location_link' => null,
            'quote_sent_status' => null,
            'quote_number' => null,
            'sale_closed' => null,
            'return_status' => null,
            'sheet_comments' => null,
            'extra_column_1' => null,
            'crm_status' => $status,
        ];
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
            'assigned_at' => now()->subHours(6),
            'notes' => 'Historial de prueba (DuplicateDetectionTestSeeder)',
            'is_current' => true,
        ]);
    }
}
