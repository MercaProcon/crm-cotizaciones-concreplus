<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MarkImportedQuotationsAsSent extends Command
{
    protected $signature = 'quotations:mark-imported-as-sent
        {--dry-run : Mostrar qué cambiaría, sin guardar nada}
        {--before= : Fecha/hora límite "YYYY-MM-DD HH:MM:SS". Obligatoria fuera de --dry-run}';

    protected $description = 'Corte histórico de una sola vez: marca como cotizacion_enviada las cotizaciones importadas de Sheets que siguen en nueva, sin asignación vigente y sin seguimiento.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $beforeOption = $this->option('before');

        $before = null;

        if ($beforeOption !== null) {
            try {
                $before = Carbon::parse($beforeOption);
            } catch (Throwable) {
                $this->error("El valor de --before no es una fecha/hora válida: \"{$beforeOption}\". Usa el formato \"YYYY-MM-DD HH:MM:SS\".");

                return self::FAILURE;
            }
        }

        if (! $dryRun && $before === null) {
            $this->error('Debes indicar --before="YYYY-MM-DD HH:MM:SS" para correr esto en modo real. Ejemplo: --before="'.now()->format('Y-m-d H:i:s').'"');
            $this->line('Esto evita marcar por error cotizaciones que Sheets acaba de importar justo antes de correr el comando.');

            return self::FAILURE;
        }

        $query = Quotation::query()
            ->whereNotNull('source_sheet')
            ->where('crm_status', Quotation::STATUS_NUEVA)
            ->whereDoesntHave('currentAssignment')
            ->whereDoesntHave('followUps')
            ->when($before, fn ($q) => $q->where('imported_at', '<=', $before));

        $total = $query->count();

        if ($total === 0) {
            $this->info('No hay cotizaciones que cumplan el criterio. Nada que hacer.');

            return self::SUCCESS;
        }

        // Nota: no se agrega orderBy() a $query porque se usa con chunkById()
        // más abajo, que necesita ordenar internamente por id para paginar de
        // forma segura mientras se actualizan las filas. El orden por fecha
        // es solo para la vista previa.
        $sample = (clone $query)->orderBy('imported_at')->limit(50)->get(['id', 'folio', 'client_name', 'zone', 'registered_at', 'imported_at']);

        $rows = $sample->map(fn (Quotation $q) => [
            $q->folio,
            $q->client_name,
            $q->zone,
            $q->registered_at?->format('d/m/Y H:i'),
            $q->imported_at?->format('d/m/Y H:i'),
        ]);

        if ($dryRun) {
            $this->warn('Modo simulación (--dry-run): no se guardará nada.');
            $this->line($before
                ? "Filtrando por imported_at <= {$before->format('Y-m-d H:i:s')}"
                : 'Sin límite de fecha (--before no especificado) — se está evaluando TODO el historial que cumple el criterio.');

            $this->table(['Folio', 'Cliente', 'Zona', 'Fecha registrada', 'Importada el'], $rows);

            if ($total > $sample->count()) {
                $this->line('... y '.($total - $sample->count()).' más.');
            }

            $this->info("Total que cambiaría de 'nueva' a 'cotizacion_enviada': {$total}");

            return self::SUCCESS;
        }

        $this->warn('⚠ Operación de corte histórico de UNA SOLA VEZ.');
        $this->warn('No está programada en el scheduler y no debe correrse de forma repetida sin revisar --before cada vez.');
        $this->line("Filtrando por imported_at <= {$before->format('Y-m-d H:i:s')}");
        $this->newLine();

        $this->table(['Folio', 'Cliente', 'Zona', 'Fecha registrada', 'Importada el'], $rows);

        if ($total > $sample->count()) {
            $this->line('... y '.($total - $sample->count()).' más.');
        }

        DB::transaction(function () use ($query) {
            $query->chunkById(200, function ($quotations) {
                foreach ($quotations as $quotation) {
                    $quotation->update(['crm_status' => Quotation::STATUS_COTIZACION_ENVIADA]);

                    ActivityLog::create([
                        'quotation_id' => $quotation->id,
                        'user_id' => null,
                        'action' => 'bulk_marked_as_sent',
                        'old_value' => Quotation::STATUS_NUEVA,
                        'new_value' => Quotation::STATUS_COTIZACION_ENVIADA,
                    ]);
                }
            });
        });

        $this->newLine();
        $this->info("Listo: {$total} cotizaciones se marcaron como 'cotizacion_enviada'.");

        return self::SUCCESS;
    }
}
