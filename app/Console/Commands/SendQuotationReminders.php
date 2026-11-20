<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\Quotation;
use App\Models\QuotationNotification;
use App\Models\ReminderLog;
use App\Models\User;
use Illuminate\Console\Command;

class SendQuotationReminders extends Command
{
    protected $signature = 'quotations:send-reminders
        {--dry-run : Calcular y mostrar qué recordatorios se enviarían, sin guardar nada}';

    protected $description = 'Envía recordatorios a la vendedora asignada (5/15 min) y avisa a coordinadoras (30 min) sobre cotizaciones asignadas sin atender.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo simulación (--dry-run): no se guardará ningún recordatorio ni notificación.');
        }

        $firstMinutes = config('reminders.first_minutes');
        $secondMinutes = config('reminders.second_minutes');
        $coordinatorMinutes = config('reminders.notify_coordinator_minutes');

        $quotations = Quotation::query()
            ->whereNotIn('crm_status', Quotation::CLOSED_STATUSES)
            ->whereHas('currentAssignment')
            ->with('currentAssignment.assignedTo')
            ->get();

        $rows = [];
        $sentVendedora = 0;
        $sentCoordinator = 0;

        foreach ($quotations as $quotation) {
            $assignment = $quotation->currentAssignment;

            if (! $assignment || ! $assignment->assignedTo) {
                continue;
            }

            if (! $quotation->isPendingAttention()) {
                continue;
            }

            $minutesElapsed = (int) floor(abs($assignment->assigned_at->diffInMinutes(now())));

            $actions = [];

            if ($minutesElapsed >= $firstMinutes) {
                if ($this->remindVendedora($quotation, $assignment, 'first_5min', $firstMinutes, $dryRun)) {
                    $actions[] = "1er recordatorio ({$firstMinutes} min)";
                    $sentVendedora++;
                }
            }

            if ($minutesElapsed >= $secondMinutes) {
                if ($this->remindVendedora($quotation, $assignment, 'second_15min', $secondMinutes, $dryRun)) {
                    $actions[] = "2do recordatorio ({$secondMinutes} min)";
                    $sentVendedora++;
                }
            }

            if ($minutesElapsed >= $coordinatorMinutes) {
                $count = $this->notifyCoordinators($quotation, $assignment, $coordinatorMinutes, $dryRun);
                if ($count > 0) {
                    $actions[] = "aviso a {$count} coordinadora(s) ({$coordinatorMinutes} min)";
                    $sentCoordinator += $count;
                }
            }

            if (! empty($actions)) {
                $rows[] = [
                    $quotation->folio,
                    $assignment->assignedTo->name,
                    $minutesElapsed,
                    implode(', ', $actions),
                ];
            }
        }

        if (! empty($rows)) {
            $this->table(['Folio', 'Vendedora', 'Minutos sin atender', 'Acción'], $rows);
        } else {
            $this->line('No hay cotizaciones que requieran recordatorio en este momento.');
        }

        $this->info(sprintf(
            'Recordatorios a vendedoras: %d%s | Avisos a coordinadoras: %d%s',
            $sentVendedora,
            $dryRun ? ' (simulado)' : '',
            $sentCoordinator,
            $dryRun ? ' (simulado)' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * Envía (o simula) un recordatorio a la vendedora asignada, evitando
     * duplicados vía reminder_logs. Devuelve true si se envió o se habría
     * enviado (dry-run), false si ya se había enviado antes.
     */
    private function remindVendedora(Quotation $quotation, Assignment $assignment, string $type, int $minutes, bool $dryRun): bool
    {
        $alreadySent = ReminderLog::where('quotation_id', $quotation->id)
            ->where('user_id', $assignment->assigned_to_user_id)
            ->where('reminder_type', $type)
            ->exists();

        if ($alreadySent) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        ReminderLog::create([
            'quotation_id' => $quotation->id,
            'user_id' => $assignment->assigned_to_user_id,
            'reminder_type' => $type,
            'scheduled_minutes' => $minutes,
            'sent_at' => now(),
            'status' => 'sent',
        ]);

        QuotationNotification::create([
            'user_id' => $assignment->assigned_to_user_id,
            'quotation_id' => $quotation->id,
            'type' => 'reminder',
            'title' => __('Recordatorio: cotización sin atender'),
            'message' => __(
                "La cotización :folio de :client lleva :minutes minutos sin atención.\nTeléfono: :phone\nZona: :zone\n\nFavor de atenderla lo antes posible.",
                [
                    'folio' => $quotation->folio,
                    'client' => $quotation->client_name,
                    'minutes' => $minutes,
                    'phone' => $quotation->phone_raw,
                    'zone' => $quotation->zone,
                ]
            ),
            'sent_at' => now(),
        ]);

        return true;
    }

    /**
     * Avisa a todas las coordinadoras activas que aún no hayan recibido este
     * aviso para esta cotización. Devuelve cuántas fueron avisadas (o se
     * habrían avisado, en dry-run).
     */
    private function notifyCoordinators(Quotation $quotation, Assignment $assignment, int $minutes, bool $dryRun): int
    {
        $coordinators = User::role('coordinadora')->where('is_active', true)->get();
        $notified = 0;

        foreach ($coordinators as $coordinator) {
            $alreadySent = ReminderLog::where('quotation_id', $quotation->id)
                ->where('user_id', $coordinator->id)
                ->where('reminder_type', 'coordinator_30min')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $notified++;

            if ($dryRun) {
                continue;
            }

            ReminderLog::create([
                'quotation_id' => $quotation->id,
                'user_id' => $coordinator->id,
                'reminder_type' => 'coordinator_30min',
                'scheduled_minutes' => $minutes,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            QuotationNotification::create([
                'user_id' => $coordinator->id,
                'quotation_id' => $quotation->id,
                'type' => 'reminder',
                'title' => __('Cotización sin atender por :minutes minutos', ['minutes' => $minutes]),
                'message' => __(
                    "La cotización :folio de :client, asignada a :vendedora, sigue sin atención después de :minutes minutos.\nTeléfono: :phone\nZona: :zone",
                    [
                        'folio' => $quotation->folio,
                        'client' => $quotation->client_name,
                        'vendedora' => $assignment->assignedTo->name,
                        'minutes' => $minutes,
                        'phone' => $quotation->phone_raw,
                        'zone' => $quotation->zone,
                    ]
                ),
                'sent_at' => now(),
            ]);
        }

        return $notified;
    }
}
