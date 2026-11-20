<?php

namespace App\Console\Commands;

use App\Services\Sheets\QuotationSheetImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportQuotationsFromSheets extends Command
{
    protected $signature = 'sheets:import
        {--tab= : Importar solo una pestaña específica (ej. Cotizaciones)}
        {--limit= : Limitar el número de filas a procesar por pestaña}
        {--dry-run : Simular la importación sin guardar nada en la base de datos}';

    protected $description = 'Importa cotizaciones desde Google Sheets hacia la tabla quotations. Solo lectura de la hoja.';

    public function handle(QuotationSheetImporter $importer): int
    {
        $spreadsheetId = config('sheets.spreadsheet_id');

        if (blank($spreadsheetId)) {
            $this->error('GOOGLE_SHEETS_SPREADSHEET_ID no está configurado en tu .env.');

            return self::FAILURE;
        }

        $configuredTabs = config('sheets.tabs', []);

        if (empty($configuredTabs)) {
            $this->error('GOOGLE_SHEETS_TABS no está configurado en tu .env.');

            return self::FAILURE;
        }

        $onlyTab = $this->option('tab');
        $tabs = $onlyTab ? [$onlyTab] : $configuredTabs;

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo simulación (--dry-run): no se guardará nada en la base de datos.');
        }

        $totals = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $autoAssignWarnings = [];

        foreach ($tabs as $tab) {
            $this->info("Leyendo pestaña: {$tab}");

            try {
                $result = $importer->importTab($spreadsheetId, $tab, $limit, $dryRun);
            } catch (Throwable $e) {
                $this->error("Error al importar la pestaña \"{$tab}\": {$e->getMessage()}");

                continue;
            }

            if (empty($result['rows'])) {
                $this->line('  (sin filas de datos)');
            } else {
                $this->table(['Fila', 'Acción', 'Folio', 'Cliente', 'Teléfono', 'Autoasignación'], $result['rows']);
            }

            $this->line(sprintf(
                '  Nuevas: %d | Actualizadas: %d | Sin cambios: %d | Omitidas: %d',
                $result['created'],
                $result['updated'],
                $result['unchanged'],
                $result['skipped']
            ));
            $this->newLine();

            $totals['created'] += $result['created'];
            $totals['updated'] += $result['updated'];
            $totals['unchanged'] += $result['unchanged'];
            $totals['skipped'] += $result['skipped'];

            foreach ($result['auto_assign_warnings'] ?? [] as $reason => $count) {
                $autoAssignWarnings[$reason] = ($autoAssignWarnings[$reason] ?? 0) + $count;
            }
        }

        $this->info(sprintf(
            'Total%s: %d nuevas, %d actualizadas, %d sin cambios, %d omitidas.',
            $dryRun ? ' (simulado)' : '',
            $totals['created'],
            $totals['updated'],
            $totals['unchanged'],
            $totals['skipped']
        ));

        // Autoasignación: solo se advierte por problemas reales de
        // configuración (vendedora por defecto no disponible o sin admin
        // activo para atribuir la asignación) — nunca rompe la importación,
        // las cotizaciones ya se guardaron aunque no se hayan podido asignar.
        if (! empty($autoAssignWarnings)) {
            $this->newLine();
            $this->warn('Autoasignación: algunas cotizaciones NO se pudieron asignar automáticamente a Inés González:');

            $labels = [
                'vendedora_por_defecto_no_disponible' => 'La vendedora configurada (CRM_DEFAULT_VENDEDORA_EMAIL) no existe, está inactiva o no tiene rol vendedora',
                'sin_admin_activo_para_atribuir_la_asignacion' => 'No hay ningún usuario admin activo para registrar quién hizo la asignación',
            ];

            foreach ($autoAssignWarnings as $reason => $count) {
                $this->warn("  - {$count} fila(s): ".($labels[$reason] ?? $reason));
            }

            $this->warn('Las cotizaciones se importaron igual, solo quedaron sin asignar. Corrige la configuración y vuelve a correr el importador, o asígnalas manualmente.');
        }

        return self::SUCCESS;
    }
}
