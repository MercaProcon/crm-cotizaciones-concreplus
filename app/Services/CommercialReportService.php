<?php

namespace App\Services;

use App\Http\Controllers\QuotationController;
use App\Models\Assignment;
use App\Models\FollowUp;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Núcleo de cálculo de reportes comerciales, extraído de ReportController
 * para que tanto la pantalla/PDF bajo demanda (/reportes) como el reporte
 * semanal automático (comando + correo) usen exactamente la misma lógica,
 * sin duplicarla. Ningún cálculo aquí es nuevo: es el mismo código que ya
 * corría dentro de ReportController, solo movido de lugar.
 */
class CommercialReportService
{
    /**
     * Umbral de "crítica para seguimiento": sin seguimiento por más de este
     * número de días. Es un umbral adicional al de "más de 3 días" ya
     * existente — representa el backlog realmente urgente. No estaba
     * definido en ningún lado del proyecto; lo dejo como constante clara y
     * fácil de ajustar si Dirección Comercial prefiere otro número.
     */
    public const CRITICAL_FOLLOW_UP_DAYS = 7;

    /**
     * Filtros compartidos: el periodo siempre se aplica sobre la fecha base
     * de la cotización (registered_at, o created_at si no existe) — nunca
     * sobre assigned_at ni sobre el follow_up. operationalVisible() se
     * aplica siempre, sin importar qué start_date se pida: si el periodo
     * arranca antes del corte operativo, el resultado real queda acotado a
     * partir de Quotation::operationalStartDate().
     */
    public function filteredQuotationsQuery(array $filters): Builder
    {
        $query = Quotation::query()->operationalVisible();

        $this->applyPeriodFilter($query, $filters);

        if (filled($filters['zone'] ?? null)) {
            $query->where('zone', $filters['zone']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('crm_status', $filters['status']);
        }

        return $query;
    }

    private function applyPeriodFilter(Builder $query, array $filters): void
    {
        if (filled($filters['start_date'] ?? null)) {
            $query->whereRaw('COALESCE(registered_at, created_at) >= ?', [
                Carbon::parse($filters['start_date'])->startOfDay(),
            ]);
        }

        if (filled($filters['end_date'] ?? null)) {
            $query->whereRaw('COALESCE(registered_at, created_at) <= ?', [
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Reporte general
    // ------------------------------------------------------------------

    public function buildGeneralReportData(array $filters): array
    {
        $quotations = $this->filteredQuotationsQuery($filters)
            ->get(['id', 'crm_status', 'zone', 'registered_at', 'created_at']);

        $assignedQuotationIds = Assignment::whereIn('quotation_id', $quotations->pluck('id'))
            ->distinct()
            ->pluck('quotation_id')
            ->flip();

        return [
            'summary' => $this->summary($quotations, $filters),
            'byVendedora' => $this->byVendedoraSummary($filters),
            'byZone' => $this->byZone($quotations, $assignedQuotationIds),
            'byStatus' => $this->byStatus($quotations),
        ];
    }

    private function summary(Collection $quotations, array $filters): array
    {
        $statusCounts = $quotations->countBy('crm_status');
        $won = $statusCounts->get(Quotation::STATUS_GANADA, 0);
        $lost = $statusCounts->get(Quotation::STATUS_PERDIDA, 0);

        return [
            'total' => $quotations->count(),
            'nueva' => $statusCounts->get(Quotation::STATUS_NUEVA, 0),
            'asignada' => $statusCounts->get(Quotation::STATUS_ASIGNADA, 0),
            'en_seguimiento' => $statusCounts->get(Quotation::STATUS_EN_SEGUIMIENTO, 0),
            'cotizacion_enviada' => $statusCounts->get(Quotation::STATUS_COTIZACION_ENVIADA, 0),
            'pendiente_respuesta' => $statusCounts->get(Quotation::STATUS_PENDIENTE_RESPUESTA, 0),
            'won' => $won,
            'lost' => $lost,
            'conversion' => $this->conversionRate($won, $lost),
            'avg_attention_minutes' => $this->averageAttentionMinutesForPeriod($filters),
            'without_follow_up' => $this->countPendingAttention($quotations),
        ];
    }

    /**
     * "Cotizaciones sin seguimiento" reutiliza la misma regla que ya usan
     * el Dashboard y los recordatorios automáticos (Quotation::isPendingAttention):
     * asignación vigente + sin follow_up de la vendedora asignada desde que
     * se le asignó. Se recalcula en lote (2 consultas totales) para no
     * hacer N+1 sobre cada cotización.
     */
    private function countPendingAttention(Collection $quotations): int
    {
        $openQuotations = $quotations->reject(
            fn (Quotation $quotation) => in_array($quotation->crm_status, Quotation::CLOSED_STATUSES, true)
        );

        if ($openQuotations->isEmpty()) {
            return 0;
        }

        $ids = $openQuotations->pluck('id');

        $currentAssignments = Assignment::where('is_current', true)
            ->whereIn('quotation_id', $ids)
            ->get(['quotation_id', 'assigned_to_user_id', 'assigned_at'])
            ->keyBy('quotation_id');

        $followUps = FollowUp::whereIn('quotation_id', $ids)
            ->get(['quotation_id', 'user_id', 'created_at'])
            ->groupBy('quotation_id');

        $pending = 0;

        foreach ($openQuotations as $quotation) {
            $assignment = $currentAssignments->get($quotation->id);

            if (! $assignment) {
                continue;
            }

            $attended = ($followUps->get($quotation->id) ?? collect())
                ->contains(fn (FollowUp $followUp) => $followUp->user_id === $assignment->assigned_to_user_id
                    && $followUp->created_at->greaterThanOrEqualTo($assignment->assigned_at));

            if (! $attended) {
                $pending++;
            }
        }

        return $pending;
    }

    /**
     * Promedio general de tiempo de primera atención dentro del periodo:
     * promedio de los promedios por vendedora que sí tuvieron al menos una
     * cotización atendida.
     */
    private function averageAttentionMinutesForPeriod(array $filters): ?int
    {
        $vendedoras = User::role('vendedora')->whereHas('assignedQuotations')->get();

        $minutes = [];

        foreach ($vendedoras as $vendedora) {
            $metrics = $this->vendorMetrics($vendedora, $filters);

            foreach ($metrics['attention_minutes'] as $value) {
                $minutes[] = $value;
            }
        }

        return count($minutes) > 0 ? (int) round(array_sum($minutes) / count($minutes)) : null;
    }

    /**
     * "Asignadas" por zona se calcula con la existencia real de registros en
     * assignments (no con crm_status) — $assignedQuotationIds ya viene
     * resuelto en una sola consulta desde buildGeneralReportData().
     */
    private function byZone(Collection $quotations, Collection $assignedQuotationIds): Collection
    {
        $grouped = $quotations->groupBy('zone');

        return collect(QuotationController::ZONES)->map(function (string $zone) use ($grouped, $assignedQuotationIds) {
            $zoneQuotations = $grouped->get($zone, collect());
            $statusCounts = $zoneQuotations->countBy('crm_status');
            $won = $statusCounts->get(Quotation::STATUS_GANADA, 0);
            $lost = $statusCounts->get(Quotation::STATUS_PERDIDA, 0);

            return [
                'zone' => $zone,
                'total' => $zoneQuotations->count(),
                'asignadas' => $zoneQuotations->filter(fn (Quotation $q) => $assignedQuotationIds->has($q->id))->count(),
                'en_seguimiento' => $statusCounts->get(Quotation::STATUS_EN_SEGUIMIENTO, 0),
                'cotizacion_enviada' => $statusCounts->get(Quotation::STATUS_COTIZACION_ENVIADA, 0),
                'pendiente_respuesta' => $statusCounts->get(Quotation::STATUS_PENDIENTE_RESPUESTA, 0),
                'won' => $won,
                'lost' => $lost,
                'pending' => $zoneQuotations->count() - $won - $lost,
                'conversion' => $this->conversionRate($won, $lost),
            ];
        });
    }

    private function byStatus(Collection $quotations): Collection
    {
        $counts = $quotations->countBy('crm_status');
        $total = $quotations->count();

        return collect(Quotation::STATUSES)->map(function (string $status) use ($counts, $total) {
            $count = $counts->get($status, 0);

            return [
                'status' => $status,
                'label' => QuotationController::STATUS_LABELS[$status],
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * "Asignadas" (con Assignment real, igual criterio que byZone) y
     * "Atendidas" (con al menos un follow_up de su vendedora, igual
     * criterio que vendorMetrics) agregadas para el resumen del reporte
     * semanal. Se calculan sumando la tabla "Por vendedora" ya calculada,
     * para que el total del resumen siempre cuadre exactamente con la suma
     * de esa tabla — no es un cálculo nuevo, solo un sum() sobre datos que
     * ya existen.
     */
    public function weeklySummaryExtras(Collection $byVendedora): array
    {
        return [
            'assigned' => $byVendedora->sum('assigned'),
            'attended' => $byVendedora->sum('attended'),
        ];
    }

    // ------------------------------------------------------------------
    // Reporte por vendedora (tabla resumen y reporte individual)
    // ------------------------------------------------------------------

    public function byVendedoraSummary(array $filters): Collection
    {
        return User::role('vendedora')
            ->whereHas('assignedQuotations')
            ->orderBy('name')
            ->get()
            ->map(function (User $vendedora) use ($filters) {
                $metrics = $this->vendorMetrics($vendedora, $filters);

                return [
                    'id' => $vendedora->id,
                    'name' => $vendedora->name,
                    'is_active' => $vendedora->is_active,
                    'assigned' => $metrics['assigned'],
                    'attended' => $metrics['attended'],
                    'without_follow_up' => $metrics['without_follow_up'],
                    'en_seguimiento' => $metrics['en_seguimiento'],
                    'cotizacion_enviada' => $metrics['cotizacion_enviada'],
                    'pendiente_respuesta' => $metrics['pendiente_respuesta'],
                    'won' => $metrics['won'],
                    'lost' => $metrics['lost'],
                    'conversion' => $metrics['conversion'],
                    'avg_attention_minutes' => $metrics['avg_attention_minutes'],
                    'last_follow_up_at' => $metrics['last_follow_up_at'],
                ];
            });
    }

    public function buildVendorReportData(User $vendedora, array $filters): array
    {
        $metrics = $this->vendorMetrics($vendedora, $filters);

        return [
            'metrics' => $metrics,
        ];
    }

    /**
     * Núcleo de cálculo por vendedora, usado por la tabla "Por vendedora",
     * el reporte individual (HTML y PDF), el promedio general de atención,
     * y ahora también por las alertas del reporte semanal.
     *
     * Reglas confirmadas:
     * - "Asignadas" = historial completo de assignments hacia esa vendedora
     *   (no solo la asignación vigente), filtrado por el periodo aplicado
     *   sobre la fecha base de la cotización.
     * - "Atendida" / "Sin seguimiento" / "Tiempo de primera atención" solo
     *   cuentan follow_ups hechos por esa misma vendedora, después de la
     *   asignación (la más reciente que tuvo hacia ella, si la cotización
     *   le fue reasignada más de una vez).
     */
    public function vendorMetrics(User $vendedora, array $filters): array
    {
        $empty = [
            'rows' => collect(),
            'assigned' => 0,
            'attended' => 0,
            'without_follow_up' => 0,
            'without_follow_up_rows' => collect(),
            'over_3_days_rows' => collect(),
            'pending_response_rows' => collect(),
            'lost_with_comment_rows' => collect(),
            'en_seguimiento' => 0,
            'cotizacion_enviada' => 0,
            'pendiente_respuesta' => 0,
            'won' => 0,
            'lost' => 0,
            'conversion' => null,
            'avg_attention_minutes' => null,
            'attention_minutes' => [],
            'last_follow_up_at' => null,
        ];

        $assignmentsByQuotation = Assignment::where('assigned_to_user_id', $vendedora->id)
            ->orderBy('assigned_at')
            ->get(['id', 'quotation_id', 'assigned_at'])
            ->groupBy('quotation_id')
            ->map(fn (Collection $group) => $group->last());

        if ($assignmentsByQuotation->isEmpty()) {
            return $empty;
        }

        $quotations = $this->filteredQuotationsQuery($filters)
            ->whereIn('id', $assignmentsByQuotation->keys())
            ->orderByDesc('registered_at')
            ->get(['id', 'folio', 'client_name', 'phone_raw', 'phone_normalized', 'zone', 'crm_status', 'registered_at', 'created_at']);

        if ($quotations->isEmpty()) {
            return $empty;
        }

        $quotationIds = $quotations->pluck('id');

        $followUpsByQuotation = FollowUp::where('user_id', $vendedora->id)
            ->whereIn('quotation_id', $quotationIds)
            ->orderBy('created_at')
            ->get(['id', 'quotation_id', 'status', 'comment', 'created_at'])
            ->groupBy('quotation_id');

        $rows = $quotations->map(function (Quotation $quotation) use ($assignmentsByQuotation, $followUpsByQuotation) {
            $assignment = $assignmentsByQuotation->get($quotation->id);
            $vendorFollowUps = $followUpsByQuotation->get($quotation->id, collect());

            $followUpsAfterAssignment = $vendorFollowUps->filter(
                fn (FollowUp $followUp) => $followUp->created_at->greaterThanOrEqualTo($assignment->assigned_at)
            );

            $firstFollowUp = $followUpsAfterAssignment->first();
            $lastFollowUp = $vendorFollowUps->last();

            return [
                'quotation' => $quotation,
                'assignment' => $assignment,
                'attended' => $firstFollowUp !== null,
                'first_follow_up' => $firstFollowUp,
                'last_follow_up' => $lastFollowUp,
                'days_without_follow_up' => $firstFollowUp === null
                    ? (int) $assignment->assigned_at->diffInDays(now())
                    : null,
            ];
        });

        $attendedRows = $rows->where('attended', true)->values();
        $withoutFollowUpRows = $rows->where('attended', false)->values();

        $attentionMinutes = $attendedRows
            ->map(fn (array $row) => abs($row['assignment']->assigned_at->diffInMinutes($row['first_follow_up']->created_at)))
            ->all();

        $statusCounts = $quotations->countBy('crm_status');
        $won = $statusCounts->get(Quotation::STATUS_GANADA, 0);
        $lost = $statusCounts->get(Quotation::STATUS_PERDIDA, 0);

        $lastFollowUpAt = $rows->pluck('last_follow_up')
            ->filter()
            ->sortByDesc(fn (FollowUp $followUp) => $followUp->created_at)
            ->first()?->created_at;

        $lostWithCommentRows = $rows->filter(
            fn (array $row) => $row['quotation']->crm_status === Quotation::STATUS_PERDIDA
        )->map(function (array $row) use ($followUpsByQuotation) {
            $lastLostFollowUp = ($followUpsByQuotation->get($row['quotation']->id) ?? collect())
                ->where('status', Quotation::STATUS_PERDIDA)
                ->last();

            return [
                'quotation' => $row['quotation'],
                'comment' => $lastLostFollowUp->comment ?? null,
            ];
        })->filter(fn (array $row) => filled($row['comment']))->values();

        return [
            'rows' => $rows,
            'assigned' => $quotations->count(),
            'attended' => $attendedRows->count(),
            'without_follow_up' => $withoutFollowUpRows->count(),
            'without_follow_up_rows' => $withoutFollowUpRows,
            'over_3_days_rows' => $withoutFollowUpRows->filter(
                fn (array $row) => $row['days_without_follow_up'] > 3
            )->values(),
            'pending_response_rows' => $rows->filter(
                fn (array $row) => $row['quotation']->crm_status === Quotation::STATUS_PENDIENTE_RESPUESTA
            )->values(),
            'lost_with_comment_rows' => $lostWithCommentRows,
            'en_seguimiento' => $statusCounts->get(Quotation::STATUS_EN_SEGUIMIENTO, 0),
            'cotizacion_enviada' => $statusCounts->get(Quotation::STATUS_COTIZACION_ENVIADA, 0),
            'pendiente_respuesta' => $statusCounts->get(Quotation::STATUS_PENDIENTE_RESPUESTA, 0),
            'won' => $won,
            'lost' => $lost,
            'conversion' => $this->conversionRate($won, $lost),
            'avg_attention_minutes' => count($attentionMinutes) > 0
                ? (int) round(array_sum($attentionMinutes) / count($attentionMinutes))
                : null,
            'attention_minutes' => $attentionMinutes,
            'last_follow_up_at' => $lastFollowUpAt,
        ];
    }

    /**
     * Alertas comerciales agregadas de TODAS las vendedoras (no una sola),
     * para el reporte semanal. Reutiliza vendorMetrics() por cada vendedora
     * y junta sus listas — mismo criterio exacto que ya usa /reportes por
     * vendedora, sin recalcular nada distinto.
     */
    public function weeklyAlerts(array $filters): array
    {
        $vendedoras = User::role('vendedora')->whereHas('assignedQuotations')->get();

        $withoutFollowUp = collect();
        $overThreeDays = collect();
        $pendingResponse = collect();
        $critical = collect();

        foreach ($vendedoras as $vendedora) {
            $metrics = $this->vendorMetrics($vendedora, $filters);

            $withoutFollowUp = $withoutFollowUp->concat(
                $metrics['without_follow_up_rows']->map(fn (array $row) => $this->alertRow($row, $vendedora))
            );

            $overThreeDays = $overThreeDays->concat(
                $metrics['over_3_days_rows']->map(fn (array $row) => $this->alertRow($row, $vendedora))
            );

            $pendingResponse = $pendingResponse->concat(
                $metrics['pending_response_rows']->map(fn (array $row) => $this->alertRow($row, $vendedora))
            );

            $critical = $critical->concat(
                $metrics['without_follow_up_rows']
                    ->filter(fn (array $row) => $row['days_without_follow_up'] > self::CRITICAL_FOLLOW_UP_DAYS)
                    ->map(fn (array $row) => $this->alertRow($row, $vendedora))
            );
        }

        return [
            'without_follow_up' => $withoutFollowUp->values(),
            'over_3_days' => $overThreeDays->values(),
            'pending_response' => $pendingResponse->values(),
            'critical' => $critical->values(),
        ];
    }

    private function alertRow(array $row, User $vendedora): array
    {
        return [
            'quotation' => $row['quotation'],
            'vendedora' => $vendedora->name,
            'days_without_follow_up' => $row['days_without_follow_up'],
        ];
    }

    /**
     * Observaciones automáticas del reporte semanal: texto generado con
     * max()/sortByDesc() sobre los mismos datos ya agregados — sin
     * interpretación adicional, mismo criterio que ya usa el PDF general.
     */
    public function observations(array $summary, Collection $byVendedora, Collection $byZone, Collection $byStatus): array
    {
        $topVendedora = $byVendedora->sortByDesc('won')->first();
        $topWithoutFollowUp = $byVendedora->sortByDesc('without_follow_up')->first();
        $topZone = $byZone->sortByDesc('total')->first();
        $topStatus = $byStatus->sortByDesc('count')->first();

        return [
            'top_vendedora' => $topVendedora && $topVendedora['won'] > 0
                ? $topVendedora['name'].' ('.$topVendedora['won'].')'
                : null,
            'top_without_follow_up' => $topWithoutFollowUp && $topWithoutFollowUp['without_follow_up'] > 0
                ? $topWithoutFollowUp['name'].' ('.$topWithoutFollowUp['without_follow_up'].')'
                : null,
            'top_zone' => $topZone && $topZone['total'] > 0
                ? $topZone['zone'].' ('.$topZone['total'].')'
                : null,
            'top_status' => $topStatus && $topStatus['count'] > 0
                ? $topStatus['label'].' ('.$topStatus['count'].')'
                : null,
        ];
    }

    public function conversionRate(int $won, int $lost): ?float
    {
        $total = $won + $lost;

        return $total > 0 ? round(($won / $total) * 100, 1) : null;
    }

    public function logoDataUri(): string
    {
        return 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/concreplus-logo.png')));
    }
}
