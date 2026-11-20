<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Quotation;
use App\Models\QuotationNotification;
use App\Models\User;
use App\Services\AssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    /**
     * Fase 3 (Concreplus): ciclo de asignación automática deshabilitado.
     * La lista original traía correos reales de vendedoras de PROCON y una
     * zona (Yucatán) que no aplican a Concreplus — se vació a propósito.
     * autoAssign() corta antes de leer esta constante; no diseñar el ciclo
     * de Concreplus aquí hasta confirmar vendedoras y reglas reales.
     */
    private const AUTO_ASSIGNMENT_CYCLE_EMAILS = [];

    /**
     * Sin uso mientras la asignación automática esté deshabilitada (ver
     * autoAssign()). Se deja documentado por si se reactiva más adelante
     * con una zona real de Concreplus.
     */
    private const AUTO_ASSIGNMENT_ZONE = 'General';

    /**
     * Umbral de similitud (índice de Jaccard por tokens, 0-1) para
     * considerar dos solicitudes del mismo teléfono como "posible duplicado
     * real". Deliberadamente alto (conservador): ante la duda, se trata
     * como cliente con una solicitud nueva y se asigna, no se omite.
     */
    private const DUPLICATE_SIMILARITY_THRESHOLD = 0.85;

    /**
     * Ventana de días entre dos cotizaciones del mismo teléfono para que
     * siquiera se evalúen como posible duplicado. Fuera de esta ventana,
     * aunque el texto sea parecido, se tratan como visitas distintas.
     */
    private const DUPLICATE_DATE_WINDOW_DAYS = 14;

    /**
     * Panel de asignación: cotizaciones nuevas y sin asignación vigente,
     * con los mismos filtros que el módulo Cotizaciones (acotados a lo
     * pendiente, no es un listado general).
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'zone', 'duplicate', 'start_date', 'end_date']);

        $quotations = $this->filteredPendingQuotations($filters)->paginate(20)->withQueryString();

        return view('assignments.index', [
            'quotations' => $quotations,
            'duplicatePhones' => $this->duplicatePhones(),
            'vendedoras' => $this->activeVendedoras(),
            'filters' => $filters,
            'zones' => QuotationController::ZONES,
        ]);
    }

    /**
     * Asigna o reasigna una cotización a una vendedora activa.
     *
     * Requiere quotations.assign si la cotización no tiene asignación vigente,
     * o quotations.reassign si ya la tiene. Se mantiene como respaldo
     * administrativo puntual; el flujo normal es la asignación automática.
     */
    public function store(Request $request, Quotation $quotation): RedirectResponse
    {
        // Corte operativo: ni manual ni automática deben tomar cotizaciones
        // anteriores a Quotation::operationalStartDate().
        abort_unless(
            Quotation::operationalVisible()->whereKey($quotation->id)->exists(),
            404,
            __('Cotización fuera del periodo operativo visible.')
        );

        $validated = $request->validate([
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $vendedora = $this->activeVendedoras()->firstWhere('id', (int) $validated['assigned_to_user_id']);

        if (! $vendedora) {
            return back()
                ->withErrors(['assigned_to_user_id' => __('Selecciona una vendedora activa válida.')])
                ->withInput();
        }

        $currentAssignment = $quotation->currentAssignment()->with('assignedTo')->first();
        $isReassignment = $currentAssignment !== null;

        $requiredPermission = $isReassignment ? 'quotations.reassign' : 'quotations.assign';
        abort_unless($request->user()->can($requiredPermission), 403);

        if ($isReassignment && $currentAssignment->assigned_to_user_id === $vendedora->id) {
            return back()->with('status', __('Esa cotización ya está asignada a :name.', ['name' => $vendedora->name]));
        }

        DB::transaction(function () use ($request, $quotation, $vendedora, $currentAssignment, $validated) {
            $this->performAssignment($quotation, $vendedora, $request->user(), $currentAssignment, $validated['notes'] ?? null);
        });

        return back()->with('status', $isReassignment
            ? __('Cotización reasignada a :name.', ['name' => $vendedora->name])
            : __('Cotización asignada a :name.', ['name' => $vendedora->name]));
    }

    /**
     * Asignación automática sobre todas las cotizaciones pendientes de
     * Yucatán que coinciden con los filtros activos, no solo la página
     * visible. Trabaja por GRUPO de teléfono (phone_normalized), no por
     * cotización individual, para no partir al mismo cliente entre varias
     * vendedoras. Por cada grupo, en este orden:
     *
     *   1. Resuelve la vendedora del grupo completo (resolveVendorForPhoneGroup):
     *      historial con una sola vendedora válida → esa; historial con más
     *      de una vendedora distinta → CONFLICTO, se omite el grupo entero;
     *      sin historial (o vendedora histórica ya no válida) → ciclo fijo,
     *      consumiendo un solo turno para TODO el grupo, no uno por fila.
     *   2. Si no hubo conflicto, dentro del grupo se agrupan las pendientes
     *      en "clusters" de posible duplicado real (misma solicitud o muy
     *      parecida, en fecha cercana). De cada cluster con más de una
     *      cotización, se asigna solo la principal; las demás se omiten,
     *      se quedan en 'nueva' (nunca se tocan) y quedan registradas para
     *      revisión manual.
     *   3. Antes de asignar la principal de un cluster, se revisa también
     *      si ya existe una principal FUERA del lote (de una corrida
     *      anterior, ya asignada) que sea posible duplicado real de ella
     *      (historicalPossibleDuplicatePrincipal). Si existe, todo el
     *      cluster se omite de nuevo — así una cotización ya marcada como
     *      posible duplicado nunca se auto-asigna sola en una segunda
     *      corrida, aunque su principal ya no esté entre las pendientes.
     *
     * Concurrencia: vuelve a correr la misma query filtrada con
     * lockForUpdate() dentro de la transacción. Como crm_status vive en la
     * propia fila de quotations y se actualiza a 'asignada' en la misma
     * transacción que crea el Assignment, InnoDB re-evalúa el WHERE
     * crm_status = 'nueva' contra el dato más reciente para cualquier fila
     * que haya tenido que esperar el lock — si dos coordinadores presionan
     * el botón a la vez, el segundo deja de ver automáticamente lo que el
     * primero ya tomó.
     */
    public function autoAssign(Request $request): RedirectResponse
    {
        // Fase 3 (Concreplus): deshabilitada a propósito. El resto del
        // método es la lógica heredada de PROCON (ciclo por Yucatán) y no
        // se ejecuta — se deja pendiente hasta definir vendedoras y reglas
        // reales de Concreplus. La asignación manual (store()) no se ve
        // afectada.
        return back()->withErrors([
            'auto_assign' => __('La asignación automática todavía no está configurada para Concreplus. Usa la asignación manual.'),
        ]);

        $filters = $request->only(['q', 'zone', 'duplicate', 'start_date', 'end_date']);

        // Si la coordinadora tiene un filtro de zona activo distinto a
        // Yucatán, no se asigna nada — la automática es exclusiva de esa
        // zona, sin importar qué otros filtros estén activos.
        $selectedZone = $filters['zone'] ?? null;

        if (filled($selectedZone) && $selectedZone !== self::AUTO_ASSIGNMENT_ZONE) {
            return back()->withErrors([
                'auto_assign' => __('La asignación automática solo aplica para cotizaciones de Yucatán. Para otras zonas usa la asignación manual.'),
            ]);
        }

        $cycle = $this->resolveAutoAssignmentCycle();

        if ($cycle === null) {
            return back()->withErrors([
                'auto_assign' => __('No se pudo ejecutar la asignación automática porque falta una vendedora del ciclo configurado, está inactiva o no tiene rol vendedora.'),
            ]);
        }

        // Se fuerza Yucatán internamente sin importar el filtro recibido
        // (incluso si venía vacío): la automática nunca debe tomar otras
        // zonas, aunque el panel no tenga ningún filtro de zona activo.
        $autoAssignFilters = array_merge($filters, ['zone' => self::AUTO_ASSIGNMENT_ZONE]);

        $candidateCount = $this->filteredPendingQuotations($autoAssignFilters)->count();
        $assignedCount = 0;
        $assignedEntries = [];
        $possibleDuplicateEntries = [];
        $vendorConflictEntries = [];

        DB::transaction(function () use (
            $request,
            $autoAssignFilters,
            $cycle,
            &$assignedCount,
            &$assignedEntries,
            &$possibleDuplicateEntries,
            &$vendorConflictEntries
        ) {
            $toAssign = $this->filteredPendingQuotations($autoAssignFilters)->lockForUpdate()->get();

            $groups = $toAssign->groupBy('phone_normalized');
            $cycleIndex = 0;

            foreach ($groups as $phoneNormalized => $groupQuotations) {
                $groupIds = $groupQuotations->pluck('id');

                $resolution = $this->resolveVendorForPhoneGroup($phoneNormalized, $groupIds);

                if ($resolution['conflict']) {
                    $conflictNames = $resolution['conflict_names']->implode(', ');

                    foreach ($groupQuotations as $quotation) {
                        ActivityLog::create([
                            'quotation_id' => $quotation->id,
                            'user_id' => $request->user()->id,
                            'action' => 'auto_assign_skipped_vendor_conflict',
                            'old_value' => null,
                            'new_value' => $conflictNames,
                        ]);

                        $vendorConflictEntries[] = [
                            'id' => $quotation->id,
                            'folio' => $quotation->folio,
                            'client_name' => $quotation->client_name,
                            'phone_raw' => $quotation->phone_raw,
                            'conflict_vendedoras' => $conflictNames,
                        ];
                    }

                    continue;
                }

                $notes = null;

                if ($resolution['vendedora'] !== null) {
                    $vendedora = $resolution['vendedora'];
                } else {
                    $vendedora = $cycle[$cycleIndex % $cycle->count()];
                    $cycleIndex++;

                    if ($resolution['has_history']) {
                        $notes = __('Cliente con historial previo, pero la vendedora anterior está inactiva o ya no tiene rol vendedora; se asignó por ciclo automático.');
                    }
                }

                $clusters = $this->clusterPhoneGroup($groupQuotations);

                foreach ($clusters as $cluster) {
                    $principal = $this->choosePrincipal($cluster);

                    // Aunque este cluster no tenga pareja duplicada DENTRO del
                    // lote actual, puede que ya exista una principal de una
                    // corrida anterior (fuera del lote, ya asignada) que sea
                    // posible duplicado real de esta. Si existe, todo el
                    // cluster se omite otra vez — nunca se auto-asigna una
                    // cotización que ya fue evaluada como duplicado real.
                    $historicalPrincipal = $this->historicalPossibleDuplicatePrincipal($principal, $groupIds);

                    if ($historicalPrincipal !== null) {
                        foreach ($cluster as $quotation) {
                            $this->recordPossibleDuplicateSkip($quotation, $historicalPrincipal, $request->user(), $possibleDuplicateEntries);
                        }

                        continue;
                    }

                    $this->performAssignment($principal, $vendedora, $request->user(), null, $notes);
                    $assignedCount++;
                    $assignedEntries[] = [
                        'id' => $principal->id,
                        'folio' => $principal->folio,
                        'vendedora' => $vendedora->name,
                    ];

                    foreach ($cluster as $quotation) {
                        if ($quotation->id === $principal->id) {
                            continue;
                        }

                        $this->recordPossibleDuplicateSkip($quotation, $principal, $request->user(), $possibleDuplicateEntries);
                    }
                }
            }
        });

        $skippedOther = max(0, $candidateCount - $assignedCount - count($possibleDuplicateEntries) - count($vendorConflictEntries));

        session()->flash('auto_assign_result', [
            'assigned' => $assignedEntries,
            'skipped_possible_duplicate' => $possibleDuplicateEntries,
            'skipped_vendor_conflict' => $vendorConflictEntries,
            'skipped_other' => $skippedOther,
        ]);

        $message = __(':count cotizaciones asignadas automáticamente.', ['count' => $assignedCount]);

        if ($assignedCount === 0 && empty($possibleDuplicateEntries) && empty($vendorConflictEntries)) {
            $message = __('No había cotizaciones pendientes que coincidieran con los filtros actuales.');
        }

        if ($skippedOther > 0) {
            $message .= ' '.__(':skipped cotizaciones se omitieron porque ya estaban asignadas o no cumplían las condiciones.', ['skipped' => $skippedOther]);
        }

        return back()->with('status', $message);
    }

    /**
     * Cuerpo único de la asignación (manual y automática). Cierra la
     * asignación anterior si existe, crea la nueva, actualiza el estado CRM,
     * registra ActivityLog y notifica a la vendedora — exactamente lo mismo
     * que ya hacía la asignación manual, ahora compartido.
     */
    /**
     * Delegado a AssignmentService (Fase 7): la misma lógica la usa ahora
     * también la autoasignación simple hacia la vendedora por defecto, para
     * no mantener las reglas de asignación en dos lugares distintos.
     * $currentAssignment se ignora — el servicio la vuelve a leer, así el
     * comportamiento es idéntico llamando desde donde sea.
     */
    private function performAssignment(Quotation $quotation, User $vendedora, User $assignedBy, ?Assignment $currentAssignment, ?string $notes): void
    {
        $this->assignments->assign($quotation, $vendedora, $assignedBy, $notes);
    }

    /**
     * Resuelve la vendedora de TODO un grupo de teléfono (no de una sola
     * cotización), buscando historial en el CRM (cualquier otra cotización,
     * cualquier zona o estado — la propiedad del cliente no depende de la
     * zona actual). $excludeIds son los IDs del propio lote pendiente, para
     * no contarse a sí mismos como "historial".
     *
     * Para cada cotización hermana se toma su assignment "ganador" (vigente
     * si existe, si no el más reciente). Si el conjunto de vendedoras
     * distintas entre esos ganadores es mayor a 1 → CONFLICTO: el teléfono
     * tiene historial repartido entre varias vendedoras, no se resuelve
     * ninguna y el grupo completo se omite en autoAssign().
     *
     * 'has_history' distingue "no había ningún assignment previo" (cliente
     * genuinamente nuevo) de "sí había (de una sola vendedora), pero ya no
     * es válida" (para poder registrar la nota correspondiente).
     *
     * @return array{has_history: bool, vendedora: ?User, conflict: bool, conflict_names: ?Collection}
     */
    private function resolveVendorForPhoneGroup(string $phoneNormalized, Collection $excludeIds): array
    {
        $siblingIds = Quotation::where('phone_normalized', $phoneNormalized)
            ->whereNotIn('id', $excludeIds)
            ->pluck('id');

        if ($siblingIds->isEmpty()) {
            return ['has_history' => false, 'vendedora' => null, 'conflict' => false, 'conflict_names' => null];
        }

        $winningAssignmentByQuotation = Assignment::whereIn('quotation_id', $siblingIds)
            ->orderByDesc('is_current')
            ->orderByDesc('assigned_at')
            ->get()
            ->groupBy('quotation_id')
            ->map(fn (Collection $group) => $group->first());

        if ($winningAssignmentByQuotation->isEmpty()) {
            return ['has_history' => false, 'vendedora' => null, 'conflict' => false, 'conflict_names' => null];
        }

        $distinctVendedoraIds = $winningAssignmentByQuotation->pluck('assigned_to_user_id')->unique()->values();

        if ($distinctVendedoraIds->count() > 1) {
            $names = User::whereIn('id', $distinctVendedoraIds)->orderBy('name')->pluck('name');

            return ['has_history' => true, 'vendedora' => null, 'conflict' => true, 'conflict_names' => $names];
        }

        $vendedora = User::role('vendedora')
            ->where('is_active', true)
            ->where('id', $distinctVendedoraIds->first())
            ->first();

        return ['has_history' => true, 'vendedora' => $vendedora, 'conflict' => false, 'conflict_names' => null];
    }

    /**
     * Agrupa las pendientes de un mismo teléfono en clusters de posible
     * duplicado real entre sí (misma solicitud o muy parecida, en fecha
     * cercana). Cada cotización cae en el primer cluster con el que
     * "empate" (isPossibleDuplicate); si no empata con ninguno, abre su
     * propio cluster. Clusters de tamaño 1 = sin duplicado, se asignan
     * directo; de tamaño 2+ = eligen una principal (choosePrincipal).
     *
     * @return Collection<int, Collection<int, Quotation>>
     */
    private function clusterPhoneGroup(Collection $groupQuotations): Collection
    {
        $sorted = $groupQuotations->sortBy('registered_at')->values();
        $clusters = collect();

        foreach ($sorted as $quotation) {
            $matchedCluster = $clusters->first(
                fn (Collection $cluster) => $cluster->contains(
                    fn (Quotation $existing) => $this->isPossibleDuplicate($existing, $quotation)
                )
            );

            if ($matchedCluster) {
                $matchedCluster->push($quotation);
            } else {
                $clusters->push(collect([$quotation]));
            }
        }

        return $clusters;
    }

    /**
     * Elige la cotización "principal" de un cluster de posibles duplicados,
     * en este orden de prioridad:
     *   1. Ya tiene Assignment vigente o crm_status distinto de 'nueva'.
     *      (En la práctica, dentro de un lote de pendientes esto casi nunca
     *      aplica —por definición llegan sin asignar y en 'nueva'— pero se
     *      evalúa igual por corrección, ej. si el dato cambiara a mitad de
     *      la corrida.)
     *   2. Ya tiene al menos un FollowUp registrado (posible si un
     *      admin/coordinadora agregó seguimiento antes de asignar).
     *   3. La más antigua por registered_at.
     */
    private function choosePrincipal(Collection $cluster): Quotation
    {
        $advanced = $cluster->first(
            fn (Quotation $quotation) => $quotation->currentAssignment !== null
                || $quotation->crm_status !== Quotation::STATUS_NUEVA
        );

        if ($advanced) {
            return $advanced;
        }

        $withFollowUp = $cluster->first(fn (Quotation $quotation) => $quotation->followUps()->exists());

        if ($withFollowUp) {
            return $withFollowUp;
        }

        return $cluster->sortBy('registered_at')->first();
    }

    /**
     * Dos cotizaciones del mismo teléfono se consideran posible duplicado
     * real solo si están dentro de la ventana de días configurada Y su
     * descripción normalizada tiene una similitud (Jaccard por tokens) igual
     * o mayor al umbral configurado. Ante texto vacío o duda, se devuelve
     * false (se trata como solicitud distinta) — regla conservadora
     * explícita: mejor asignar de más que ocultar una solicitud real.
     */
    private function isPossibleDuplicate(Quotation $a, Quotation $b): bool
    {
        $descriptionA = $this->normalizeDescription($a->description);
        $descriptionB = $this->normalizeDescription($b->description);

        if ($descriptionA === '' || $descriptionB === '') {
            return false;
        }

        $daysApart = abs($a->registered_at->diffInDays($b->registered_at));

        if ($daysApart > self::DUPLICATE_DATE_WINDOW_DAYS) {
            return false;
        }

        return $this->descriptionSimilarity($descriptionA, $descriptionB) >= self::DUPLICATE_SIMILARITY_THRESHOLD;
    }

    /**
     * Busca si ya existe, FUERA del lote pendiente actual, una cotización
     * del mismo teléfono que sea posible duplicado real de $quotation
     * (misma regla que isPossibleDuplicate: ventana de días + similitud de
     * descripción). No importa su estado — normalmente será una principal
     * ya asignada en una corrida anterior, pero se revisa cualquier
     * cotización del teléfono, sin filtrar por crm_status.
     *
     * Esto es lo que evita el bug de que una cotización omitida como
     * "posible duplicado real" en una corrida se auto-asigne sola en la
     * siguiente corrida, una vez que su principal ya no está en el lote
     * de pendientes (porque ya fue asignada).
     */
    private function historicalPossibleDuplicatePrincipal(Quotation $quotation, Collection $excludeIds): ?Quotation
    {
        $candidates = Quotation::where('phone_normalized', $quotation->phone_normalized)
            ->whereNotIn('id', $excludeIds)
            ->get(['id', 'folio', 'description', 'registered_at']);

        foreach ($candidates as $candidate) {
            if ($this->isPossibleDuplicate($candidate, $quotation)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Deja constancia de que $quotation se omitió por posible duplicado de
     * $principal: nunca cambia crm_status, nunca crea Assignment. Solo
     * registra ActivityLog si no existe ya uno idéntico (mismo quotation_id,
     * misma acción, mismo principal) — así una tercera, cuarta, etc. corrida
     * no acumula logs repetidos del mismo caso — pero siempre agrega la
     * entrada al reporte en pantalla, para que el aviso amarillo aparezca
     * en cada corrida mientras la cotización siga pendiente de revisión.
     *
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function recordPossibleDuplicateSkip(Quotation $quotation, Quotation $principal, User $actor, array &$entries): void
    {
        $alreadyLogged = ActivityLog::where('quotation_id', $quotation->id)
            ->where('action', 'auto_assign_skipped_possible_duplicate')
            ->where('new_value', $principal->folio)
            ->exists();

        if (! $alreadyLogged) {
            ActivityLog::create([
                'quotation_id' => $quotation->id,
                'user_id' => $actor->id,
                'action' => 'auto_assign_skipped_possible_duplicate',
                'old_value' => null,
                'new_value' => $principal->folio,
            ]);
        }

        $entries[] = [
            'id' => $quotation->id,
            'folio' => $quotation->folio,
            'client_name' => $quotation->client_name,
            'phone_raw' => $quotation->phone_raw,
            'principal_id' => $principal->id,
            'principal_folio' => $principal->folio,
        ];
    }

    /**
     * Minúsculas, sin acentos (Str::ascii, ya incluido en Laravel), sin
     * puntuación, espacios colapsados. Base para comparar solicitudes sin
     * que diferencias de formato/capitalización afecten la similitud.
     */
    private function normalizeDescription(?string $description): string
    {
        if (blank($description)) {
            return '';
        }

        $ascii = Str::ascii($description);
        $lower = mb_strtolower($ascii);
        $noPunctuation = preg_replace('/[^a-z0-9\s]/', ' ', $lower);
        $collapsed = preg_replace('/\s+/', ' ', (string) $noPunctuation);

        return trim((string) $collapsed);
    }

    /**
     * Índice de Jaccard por tokens: tamaño de la intersección entre el
     * tamaño de la unión de las palabras de ambos textos ya normalizados.
     * Insensible al orden de las palabras (a diferencia de similar_text()).
     */
    private function descriptionSimilarity(string $normalizedA, string $normalizedB): float
    {
        $tokensA = array_unique(array_filter(explode(' ', $normalizedA)));
        $tokensB = array_unique(array_filter(explode(' ', $normalizedB)));

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        return count($union) > 0 ? count($intersection) / count($union) : 0.0;
    }

    /**
     * Resuelve el ciclo fijo de vendedoras por correo. Devuelve null (y no
     * ejecuta nada) si alguna falta, está inactiva o no tiene rol vendedora
     * — nunca asigna con un ciclo incompleto.
     */
    private function resolveAutoAssignmentCycle(): ?Collection
    {
        $cycle = collect(self::AUTO_ASSIGNMENT_CYCLE_EMAILS)->map(
            fn (string $email) => User::role('vendedora')
                ->where('is_active', true)
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first()
        );

        if ($cycle->contains(null)) {
            return null;
        }

        return $cycle;
    }

    /**
     * Query base del panel: pendientes de asignar (nueva, sin asignación
     * vigente), con los mismos filtros que Cotizaciones. Se reutiliza tanto
     * para pintar la tabla como para la asignación automática, para que el
     * botón respete exactamente lo que la coordinadora está viendo.
     */
    private function filteredPendingQuotations(array $filters): Builder
    {
        $query = Quotation::query()
            ->operationalVisible()
            ->where('crm_status', Quotation::STATUS_NUEVA)
            ->whereDoesntHave('currentAssignment');

        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('folio', 'like', "%{$term}%")
                    ->orWhere('client_name', 'like', "%{$term}%")
                    ->orWhere('phone_raw', 'like', "%{$term}%")
                    ->orWhere('phone_normalized', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (filled($filters['zone'] ?? null)) {
            $query->where('zone', $filters['zone']);
        }

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

        if (in_array($filters['duplicate'] ?? null, ['yes', 'no'], true)) {
            $duplicatePhones = $this->duplicatePhones();

            if ($filters['duplicate'] === 'yes') {
                $query->whereIn('phone_normalized', $duplicatePhones);
            } else {
                $query->whereNotIn('phone_normalized', $duplicatePhones);
            }
        }

        return $query->orderByRaw('COALESCE(registered_at, created_at) asc')->orderBy('id');
    }

    /**
     * Teléfonos duplicados contra toda la tabla de cotizaciones (no solo la
     * página visible), para que el badge de la tabla y el filtro "Duplicado"
     * usen siempre el mismo criterio.
     */
    private function duplicatePhones(): array
    {
        return Quotation::query()
            ->operationalVisible()
            ->select('phone_normalized')
            ->groupBy('phone_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone_normalized')
            ->all();
    }

    private function activeVendedoras()
    {
        return User::query()
            ->role('vendedora')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
