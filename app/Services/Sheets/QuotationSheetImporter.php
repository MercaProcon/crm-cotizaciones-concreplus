<?php

namespace App\Services\Sheets;

use App\Models\Quotation;
use App\Services\AssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Revolution\Google\Sheets\Facades\Sheets;
use Throwable;

class QuotationSheetImporter
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    // Concreplus: una sola hoja "Cotizaciones" con columnas A-L (sin la
    // columna extra "M" que sí tenía PROCON, y sin zonas por pestaña).
    private const COLUMN_COUNT = 12;

    /**
     * Concreplus no maneja zonas por estado (una sola operación general).
     * La columna `zone` de la BD no se eliminó (ver Fase 3), así que cada
     * fila importada se guarda con este valor fijo.
     */
    private const DEFAULT_ZONE = 'General';

    /**
     * Fase 10: un phone_normalized más corto que esto no sirve para nada en
     * el CRM (no se puede buscar, no se puede mandar WhatsApp, y colisiona
     * con otras filas igual de inválidas en Duplicados si se deja pasar
     * vacío). Número general, no depende de palabras en el nombre.
     */
    private const MIN_PHONE_DIGITS = 10;

    /**
     * Importa una pestaña completa (o hasta $limit filas).
     *
     * @return array{created: int, updated: int, unchanged: int, skipped: int, rows: array<int, array>}
     */
    public function importTab(string $spreadsheetId, string $tab, ?int $limit, bool $dryRun): array
    {
        $rows = Sheets::spreadsheet($spreadsheetId)
            ->sheet($tab)
            ->range('A2:L')
            ->all();

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        $summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'rows' => [], 'auto_assign_warnings' => []];

        foreach ($rows as $index => $row) {
            $sheetRow = $index + 2; // la fila 1 de la hoja son los encabezados

            $outcome = $this->processRow($tab, $sheetRow, $row, $dryRun);

            $summary[$outcome['action']]++;
            $summary['rows'][] = [
                $sheetRow,
                $outcome['label'],
                $outcome['folio'] ?? '—',
                $outcome['client'] ?? '—',
                $outcome['phone'] ?? '—',
                $this->autoAssignLabel($outcome['auto_assign'] ?? null),
            ];

            // Solo 'vendedora_por_defecto_no_disponible' y
            // 'sin_admin_activo_para_atribuir_la_asignacion' son problemas
            // reales de configuración — el resto ('ya_tiene_asignacion_vigente',
            // 'no_esta_en_estado_nueva', null) son resultados esperados y no
            // ameritan una advertencia.
            if (in_array($outcome['auto_assign'] ?? null, ['vendedora_por_defecto_no_disponible', 'sin_admin_activo_para_atribuir_la_asignacion'], true)) {
                $summary['auto_assign_warnings'][$outcome['auto_assign']] = ($summary['auto_assign_warnings'][$outcome['auto_assign']] ?? 0) + 1;
            }
        }

        return $summary;
    }

    /**
     * $reason viene de AssignmentService::autoAssignToDefaultVendor(): null
     * significa que SÍ asignó (su contrato de éxito). 'no_intentado' es
     * nuestro propio valor para filas donde ni siquiera se llamó al
     * servicio (dry-run, fila sin cambios, fila omitida) — nunca se
     * confunde con el null de "éxito" porque son literales distintos.
     */
    private function autoAssignLabel(?string $reason): string
    {
        return match ($reason) {
            'no_intentado' => '—',
            null => 'Asignada a Inés González',
            'ya_tiene_asignacion_vigente' => 'Ya asignada',
            'no_esta_en_estado_nueva' => 'No aplica (no es nueva)',
            'vendedora_por_defecto_no_disponible' => 'NO asignada: vendedora no disponible',
            'sin_admin_activo_para_atribuir_la_asignacion' => 'NO asignada: sin admin activo',
            default => (string) $reason,
        };
    }

    private function processRow(string $tab, int $sheetRow, array $row, bool $dryRun): array
    {
        $row = array_pad($row, self::COLUMN_COUNT, '');
        $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

        [$sheetAssignedTo, $rawDate, $clientName, $phoneRaw, $description, $sheetStatus,
            $locationLink, $quoteSentStatus, $quoteNumber, $saleClosed, $returnStatus,
            $sheetComments] = $row;

        if ($clientName === '' && $phoneRaw === '') {
            return ['action' => 'skipped', 'label' => 'Fila vacía (sin cliente ni teléfono)', 'auto_assign' => 'no_intentado'];
        }

        $phoneNormalized = $this->normalizePhone($phoneRaw);

        // Fase 10: teléfono inválido o vacío (incluye texto sin dígitos como
        // "cliente" o "desconocido", que normalizePhone() deja en cadena
        // vacía) — regla general por cantidad de dígitos, no por palabras.
        if (strlen($phoneNormalized) < self::MIN_PHONE_DIGITS) {
            return [
                'action' => 'skipped',
                'label' => 'Omitida: teléfono inválido o vacío',
                'client' => $clientName,
                'phone' => $phoneRaw,
                'auto_assign' => 'no_intentado',
            ];
        }

        $parsedDate = $this->parseSheetDate($rawDate);

        // Fase 10: fecha vacía o que no se pudo interpretar — ya no se
        // rellena con now() (eso dejaba que históricos mal capturados
        // entraran como si fueran de hoy).
        if ($parsedDate === null) {
            return [
                'action' => 'skipped',
                'label' => 'Omitida: fecha vacía o inválida',
                'client' => $clientName,
                'phone' => $phoneRaw,
                'auto_assign' => 'no_intentado',
            ];
        }

        [$registeredAt, $isAmbiguous] = $parsedDate;

        // Fase 10: corte operativo de Concreplus. Todo lo anterior a esta
        // fecha se considera histórico y se omite por completo — no crea,
        // no actualiza, no autoasigna, no notifica. Los registros que ya
        // existían en la base de corridas anteriores NO se tocan (se dejan
        // tal cual), simplemente esta corrida no vuelve a procesarlos.
        $operationalStartDate = config('crm.operational_start_date');

        if ($registeredAt->format('Y-m-d') < $operationalStartDate) {
            return [
                'action' => 'skipped',
                'label' => "Omitida: histórico, anterior al corte operativo ({$operationalStartDate})",
                'client' => $clientName,
                'phone' => $phoneRaw,
                'auto_assign' => 'no_intentado',
            ];
        }

        $sheetFields = [
            'sheet_assigned_to' => $sheetAssignedTo !== '' ? $sheetAssignedTo : null,
            'registered_at' => $registeredAt,
            'zone' => self::DEFAULT_ZONE,
            'client_name' => $clientName,
            'phone_raw' => $phoneRaw,
            'phone_normalized' => $phoneNormalized,
            'description' => $description !== '' ? $description : null,
            'sheet_status' => $sheetStatus !== '' ? $sheetStatus : null,
            'location_link' => $locationLink !== '' ? $locationLink : null,
            'quote_sent_status' => $quoteSentStatus !== '' ? $quoteSentStatus : null,
            'quote_number' => $quoteNumber !== '' ? $quoteNumber : null,
            'sale_closed' => $saleClosed !== '' ? $saleClosed : null,
            'return_status' => $returnStatus !== '' ? $returnStatus : null,
            'sheet_comments' => $sheetComments !== '' ? $sheetComments : null,
        ];

        $sourceHash = hash('sha256', $tab.'|'.implode('|', $row));

        $existing = $isAmbiguous
            ? Quotation::where('source_sheet', $tab)
                ->where('source_row', $sheetRow)
                ->first()
            : Quotation::where('source_sheet', $tab)
                ->where('phone_normalized', $phoneNormalized)
                ->where('registered_at', $registeredAt->format('Y-m-d H:i:s'))
                ->where('client_name', $clientName)
                ->first();

        if ($existing) {
            if ($existing->source_hash === $sourceHash) {
                return [
                    'action' => 'unchanged',
                    'label' => 'Sin cambios',
                    'folio' => $existing->folio,
                    'client' => $clientName,
                    'phone' => $phoneRaw,
                    'auto_assign' => 'no_intentado',
                ];
            }

            // El contenido cambió para este registro, pero antes de guardar
            // confirmamos que el nuevo hash no le pertenezca ya a OTRO
            // registro (por ejemplo, una fila que se movió de posición en la
            // hoja). source_hash es unique, así que nunca debemos intentar
            // guardar un hash que ya existe en otra fila.
            $hashUsedByAnother = Quotation::where('source_hash', $sourceHash)
                ->where('id', '!=', $existing->id)
                ->exists();

            if ($hashUsedByAnother) {
                return [
                    'action' => 'skipped',
                    'label' => 'Omitida: mismo contenido ya importado en otro registro',
                    'folio' => $existing->folio,
                    'client' => $clientName,
                    'phone' => $phoneRaw,
                    'auto_assign' => 'no_intentado',
                ];
            }

            $autoAssignReason = 'no_intentado';

            if (! $dryRun) {
                $existing->fill(array_merge($sheetFields, [
                    'source_row' => $sheetRow,
                    'source_hash' => $sourceHash,
                    'imported_at' => now(),
                ]));
                $existing->save();

                $autoAssignReason = $this->assignments->autoAssignToDefaultVendor($existing);
            }

            return [
                'action' => 'updated',
                'label' => $dryRun ? 'Se actualizaría' : 'Actualizada',
                'folio' => $existing->folio,
                'client' => $clientName,
                'phone' => $phoneRaw,
                'auto_assign' => $autoAssignReason,
            ];
        }

        // No hubo coincidencia por la llave preferente ni por la de
        // respaldo. Antes de crear un registro nuevo, verificamos que este
        // contenido exacto no se haya importado ya bajo otra combinación de
        // llave (la fila pudo haberse movido de posición en la hoja entre
        // corridas). source_hash es unique: si ya existe, jamás debemos
        // intentar insertarlo de nuevo.
        $existingByHash = Quotation::where('source_hash', $sourceHash)->first();

        if ($existingByHash) {
            return [
                'action' => 'skipped',
                'label' => 'Omitida: duplicada ya importada (mismo contenido)',
                'folio' => $existingByHash->folio,
                'client' => $clientName,
                'phone' => $phoneRaw,
                'auto_assign' => 'no_intentado',
            ];
        }

        $folio = $dryRun
            ? $this->folioPrefix($tab, $registeredAt).'??????'
            : $this->generateFolio($tab, $registeredAt);

        $autoAssignReason = 'no_intentado';

        if (! $dryRun) {
            $quotation = Quotation::create(array_merge($sheetFields, [
                'folio' => $folio,
                'source_sheet' => $tab,
                'source_row' => $sheetRow,
                'source_hash' => $sourceHash,
                'imported_at' => now(),
                'crm_status' => Quotation::STATUS_NUEVA,
            ]));

            $autoAssignReason = $this->assignments->autoAssignToDefaultVendor($quotation);
        }

        return [
            'action' => 'created',
            'label' => $dryRun ? 'Se crearía' : 'Creada',
            'folio' => $folio,
            'client' => $clientName,
            'phone' => $phoneRaw,
            'auto_assign' => $autoAssignReason,
        ];
    }

    /**
     * Normaliza el teléfono a los últimos 10 dígitos, sin importar el
     * formato de origen (espacios, guiones, +52, lada con 1, etc.).
     */
    private function normalizePhone(string $phoneRaw): string
    {
        $digits = preg_replace('/\D+/', '', $phoneRaw) ?? '';

        return substr($digits, -10);
    }

    /**
     * Intenta parsear la fecha de la hoja probando primero formatos con hora
     * (no ambiguos) y luego solo-fecha (ambiguos). No usamos
     * Carbon::createFromFormat con un formato sin hora como único intento,
     * porque en ese caso Carbon rellena la hora con la hora actual en vez de
     * medianoche, lo que rompería la detección de "sin hora exacta".
     *
     * Fase 5 (Concreplus, dato real): la hoja real de Concreplus escribe
     * "0:00:00" en TODAS las filas (Google Sheets rellena medianoche cuando
     * la celda solo tiene fecha, no hora real) — no es que la vendedora haya
     * registrado la medianoche exacta. Si tratáramos esto como "hora exacta
     * no ambigua", dos filas distintas del mismo cliente/teléfono el mismo
     * día (contacto repetido) colapsarían en un solo registro que se
     * sobreescribe cada corrida, perdiendo una de las dos solicitudes. Por
     * eso: si la hora resultante es exactamente medianoche, se trata igual
     * que una fecha ambigua (se ancla por fila de la hoja, no por
     * teléfono+fecha+cliente).
     *
     * Fase 10: ya NO cae en now() cuando la fecha viene vacía o no se puede
     * interpretar — devuelve null y processRow() omite la fila con una
     * etiqueta clara ("fecha vacía o inválida"). Antes, una fecha vacía se
     * volvía silenciosamente "hoy", lo que dejaba que filas históricas mal
     * capturadas se colaran como si fueran leads del día del corte operativo.
     *
     * @return array{0: \Carbon\Carbon, 1: bool}|null [fecha, esAmbigua], o null si la fecha viene vacía o no se pudo interpretar
     */
    private function parseSheetDate(string $rawDate): ?array
    {
        if ($rawDate === '') {
            return null;
        }

        $formatsWithTime = ['d/m/Y H:i:s', 'd/m/Y H:i', 'm/d/Y H:i:s', 'm/d/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'];
        $formatsDateOnly = ['d/m/Y', 'm/d/Y', 'Y-m-d'];

        foreach ($formatsWithTime as $format) {
            if ($parsed = $this->tryParse($format, $rawDate)) {
                return [$parsed, $this->isMidnight($parsed)];
            }
        }

        foreach ($formatsDateOnly as $format) {
            if ($parsed = $this->tryParse($format, $rawDate)) {
                return [$parsed->startOfDay(), true];
            }
        }

        try {
            $parsed = Carbon::parse($rawDate);
            $hasTime = str_contains($rawDate, ':') && ! $this->isMidnight($parsed);

            return [$hasTime ? $parsed : $parsed->startOfDay(), ! $hasTime];
        } catch (Throwable) {
            return null;
        }
    }

    private function tryParse(string $format, string $value): ?Carbon
    {
        try {
            $parsed = Carbon::createFromFormat($format, $value);

            return $parsed instanceof Carbon ? $parsed : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function isMidnight(Carbon $parsed): bool
    {
        return $parsed->format('H:i:s') === '00:00:00';
    }

    private function generateFolio(string $tab, Carbon $registeredAt): string
    {
        $prefix = $this->folioPrefix($tab, $registeredAt);

        $lastNumber = DB::table('quotations')
            ->where('folio', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(folio, ?) AS UNSIGNED)) as max_seq', [strlen($prefix) + 1])
            ->value('max_seq');

        return $prefix.str_pad((int) $lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Folio interno generado por el CRM (nunca a partir de "No. de
     * cotización" de la hoja: ese valor es texto libre que la vendedora
     * llena después, casi siempre vacío en cotizaciones nuevas y sin
     * garantía de ser único — ver notas de Fase 4). Para Concreplus, con
     * una sola pestaña "Cotizaciones", el prefijo sale de las primeras 3
     * letras del nombre de la pestaña: "COT-20260722-000001".
     */
    private function folioPrefix(string $tab, Carbon $registeredAt): string
    {
        $code = strtoupper(substr($tab, 0, 3));

        return $code.'-'.$registeredAt->format('Ymd').'-';
    }
}
