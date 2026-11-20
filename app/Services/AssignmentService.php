<?php

namespace App\Services;

use App\Mail\QuotationAssignedMail;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Quotation;
use App\Models\QuotationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Lógica de asignación compartida entre la asignación manual
 * (AssignmentController) y la autoasignación simple de Concreplus (Fase 7).
 * Un solo lugar para crear el Assignment, mover crm_status, dejar el
 * ActivityLog y notificar a la vendedora — ninguno de los dos flujos debe
 * duplicar estas reglas por separado.
 */
class AssignmentService
{
    /**
     * Asigna o reasigna una cotización a una vendedora. No valida permisos
     * ni el estado activo de la vendedora — eso es responsabilidad de quien
     * llama (AssignmentController::store() ya lo hace; autoAssignToDefaultVendor()
     * de aquí mismo también).
     */
    public function assign(Quotation $quotation, User $vendedora, User $assignedBy, ?string $notes = null): void
    {
        $currentAssignment = $quotation->currentAssignment;
        $isReassignment = $currentAssignment !== null;
        $previousVendedoraName = $currentAssignment?->assignedTo?->name;

        if ($currentAssignment) {
            $currentAssignment->update(['is_current' => false]);
        }

        $assignment = Assignment::create([
            'quotation_id' => $quotation->id,
            'assigned_by_user_id' => $assignedBy->id,
            'assigned_to_user_id' => $vendedora->id,
            'assigned_at' => now(),
            'notes' => $notes,
            'is_current' => true,
        ]);

        $quotation->update(['crm_status' => Quotation::STATUS_ASIGNADA]);

        ActivityLog::create([
            'quotation_id' => $quotation->id,
            'user_id' => $assignedBy->id,
            'action' => $isReassignment ? 'reassigned' : 'assigned',
            'old_value' => $previousVendedoraName,
            'new_value' => $vendedora->name,
        ]);

        QuotationNotification::create([
            'user_id' => $vendedora->id,
            'quotation_id' => $quotation->id,
            'type' => $isReassignment ? 'reassigned' : 'assigned',
            'title' => $isReassignment ? __('Cotización reasignada a ti') : __('Nueva cotización asignada'),
            'message' => __(
                "Cliente: :client\nTeléfono: :phone\nSolicitud: :description\nAsignada por: :assignedBy\nHora de asignación: :time\n\nFavor de atenderla lo antes posible.",
                [
                    'client' => $quotation->client_name,
                    'phone' => $quotation->phone_raw,
                    'description' => $quotation->description ?? __('Sin detalle'),
                    'assignedBy' => $assignedBy->name,
                    'time' => now()->format('h:i A'),
                ]
            ),
            'sent_at' => now(),
        ]);

        // Fase 15: correo real además de la notificación in-app -- un fallo
        // de envío (SMTP caido, etc.) nunca debe tumbar la asignación ni la
        // importación que la dispara, solo se registra en el log.
        try {
            Mail::to($vendedora->email)->send(
                new QuotationAssignedMail($quotation, $assignment, $isReassignment)
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el correo de cotización asignada', [
                'quotation_id' => $quotation->id,
                'vendedora_id' => $vendedora->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Autoasignación simple de Concreplus (Fase 7): SIEMPRE a la misma
     * vendedora configurada en config('crm.default_vendedora_email') —
     * nunca un ciclo/round-robin, nunca por zona, nunca las vendedoras de
     * PROCON. Pensada para llamarse tras crear/actualizar una cotización en
     * el importador, y también para el respaldo inicial de cotizaciones ya
     * existentes.
     *
     * No asigna (y no lanza excepción) si la cotización ya tiene asignación
     * vigente, si no está en estado "nueva", o si la vendedora configurada
     * no existe/no está activa/no tiene rol vendedora — en ese último caso
     * devuelve un motivo para que quien llama pueda registrar un aviso claro
     * sin romper la importación ni asignar a nadie más.
     *
     * @return string|null null si asignó correctamente; motivo (string) si no asignó
     */
    public function autoAssignToDefaultVendor(Quotation $quotation): ?string
    {
        if ($quotation->currentAssignment !== null) {
            return 'ya_tiene_asignacion_vigente';
        }

        if ($quotation->crm_status !== Quotation::STATUS_NUEVA) {
            return 'no_esta_en_estado_nueva';
        }

        $defaultEmail = config('crm.default_vendedora_email');

        $vendedora = User::query()
            ->role('vendedora')
            ->where('is_active', true)
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $defaultEmail)])
            ->first();

        if (! $vendedora) {
            return 'vendedora_por_defecto_no_disponible';
        }

        $assignedBy = User::query()->role('admin')->where('is_active', true)->orderBy('id')->first();

        if (! $assignedBy) {
            return 'sin_admin_activo_para_atribuir_la_asignacion';
        }

        $this->assign($quotation, $vendedora, $assignedBy);

        return null;
    }
}
