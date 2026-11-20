<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    // Copia de Concreplus (Fase 3): sin zonas por estado como PROCON — una
    // sola tabla general. La columna `zone` de la BD no se eliminó (evitar
    // migración destructiva), así que se mantiene un único valor sentinela
    // para no romper la validación ni las consultas existentes. Fase 4
    // (importador) debe escribir este mismo valor en cada fila importada.
    public const ZONES = ['General'];

    public const STATUS_LABELS = [
        Quotation::STATUS_NUEVA => 'Nueva',
        Quotation::STATUS_ASIGNADA => 'Asignada',
        Quotation::STATUS_EN_SEGUIMIENTO => 'En seguimiento',
        Quotation::STATUS_COTIZACION_ENVIADA => 'Cotización enviada',
        Quotation::STATUS_PENDIENTE_RESPUESTA => 'Pendiente de respuesta',
        Quotation::STATUS_GANADA => 'Ganada',
        Quotation::STATUS_PERDIDA => 'Perdida',
        Quotation::STATUS_DUPLICADA => 'Duplicada',
        Quotation::STATUS_CANCELADA => 'Cancelada',
    ];

    /**
     * Listado de cotizaciones con buscador global y filtros.
     *
     * Las vendedoras no ven el listado completo por defecto: solo cuando
     * hay una búsqueda o filtro activo, para que esta pantalla se use como
     * consulta de historial y no como una segunda "Mis cotizaciones".
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->only(['q', 'zone', 'crm_status', 'assigned_to']);
        $hasActiveFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();

        $restrictedToSearch = $user->hasRole('vendedora') && ! $user->can('quotations.view.all');

        $quotations = collect();

        if (! $restrictedToSearch || $hasActiveFilters) {
            $query = Quotation::query()
                ->operationalVisible()
                ->with('currentAssignment.assignedTo')
                ->latest('registered_at');

            if (filled($filters['q'] ?? null)) {
                $term = $filters['q'];
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('folio', 'like', "%{$term}%")
                        ->orWhere('client_name', 'like', "%{$term}%")
                        ->orWhere('phone_raw', 'like', "%{$term}%")
                        ->orWhere('phone_normalized', 'like', "%{$term}%")
                        ->orWhere('sheet_assigned_to', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            }

            if (filled($filters['zone'] ?? null)) {
                $query->where('zone', $filters['zone']);
            }

            if (filled($filters['crm_status'] ?? null)) {
                $query->where('crm_status', $filters['crm_status']);
            }

            if (filled($filters['assigned_to'] ?? null)) {
                $query->whereHas('currentAssignment', function ($subQuery) use ($filters) {
                    $subQuery->where('assigned_to_user_id', $filters['assigned_to']);
                });
            }

            $quotations = $query->paginate(20)->withQueryString();
        }

        return view('quotations.index', [
            'quotations' => $quotations,
            'filters' => $filters,
            'zones' => self::ZONES,
            'statusLabels' => self::STATUS_LABELS,
            'vendedoras' => User::role('vendedora')->orderBy('name')->get(['id', 'name']),
            'restrictedToSearch' => $restrictedToSearch,
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }

    /**
     * Detalle de una cotización: datos, historial por teléfono, asignación
     * y seguimiento (estos dos últimos con controles solo si el usuario
     * tiene permiso y, en el caso de vendedora, es la dueña de la asignación).
     */
    public function show(Request $request, Quotation $quotation): View
    {
        // Corte operativo: una cotización anterior a Quotation::operationalStartDate()
        // no debe poder abrirse ni por URL directa, aunque el ID exista. El
        // historial por teléfono (abajo) sí se mantiene completo a propósito.
        abort_unless(
            Quotation::operationalVisible()->whereKey($quotation->id)->exists(),
            404,
            __('Cotización fuera del periodo operativo visible.')
        );

        $quotation->load('currentAssignment.assignedTo');

        $phoneHistory = Quotation::where('phone_normalized', $quotation->phone_normalized)
            ->where('id', '!=', $quotation->id)
            ->orderByDesc('registered_at')
            ->get();

        $followUpStatusLabels = collect(Quotation::FOLLOW_UP_STATUSES)
            ->mapWithKeys(fn ($status) => [$status => self::STATUS_LABELS[$status]]);

        return view('quotations.show', [
            'quotation' => $quotation,
            'phoneHistory' => $phoneHistory,
            'statusLabels' => self::STATUS_LABELS,
            'vendedoras' => User::role('vendedora')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canAddFollowUp' => $quotation->canReceiveFollowUpFrom($request->user()),
            'followUpStatusLabels' => $followUpStatusLabels,
            'followUps' => $quotation->followUps()->with('user')->latest()->get(),
        ]);
    }
}
