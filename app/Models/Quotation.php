<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'folio',
    'source_sheet',
    'source_row',
    'source_hash',
    'imported_at',
    'sheet_assigned_to',
    'registered_at',
    'zone',
    'client_name',
    'phone_raw',
    'phone_normalized',
    'description',
    'sheet_status',
    'location_link',
    'quote_sent_status',
    'quote_number',
    'sale_closed',
    'return_status',
    'sheet_comments',
    'extra_column_1',
    'crm_status',
    'first_attention_at',
])]
class Quotation extends Model
{
    use HasFactory;

    public const STATUS_NUEVA = 'nueva';

    public const STATUS_ASIGNADA = 'asignada';

    public const STATUS_EN_SEGUIMIENTO = 'en_seguimiento';

    public const STATUS_COTIZACION_ENVIADA = 'cotizacion_enviada';

    public const STATUS_PENDIENTE_RESPUESTA = 'pendiente_respuesta';

    public const STATUS_GANADA = 'ganada';

    public const STATUS_PERDIDA = 'perdida';

    public const STATUS_DUPLICADA = 'duplicada';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUSES = [
        self::STATUS_NUEVA,
        self::STATUS_ASIGNADA,
        self::STATUS_EN_SEGUIMIENTO,
        self::STATUS_COTIZACION_ENVIADA,
        self::STATUS_PENDIENTE_RESPUESTA,
        self::STATUS_GANADA,
        self::STATUS_PERDIDA,
        self::STATUS_DUPLICADA,
        self::STATUS_CANCELADA,
    ];

    /**
     * Estados que una vendedora, coordinadora o admin puede aplicar al agregar
     * un seguimiento. Nueva, Asignada, Duplicada y Cancelada quedan fuera:
     * son de sistema o de Neysi/Admin, no de la operación diaria de seguimiento.
     */
    public const FOLLOW_UP_STATUSES = [
        self::STATUS_EN_SEGUIMIENTO,
        self::STATUS_COTIZACION_ENVIADA,
        self::STATUS_PENDIENTE_RESPUESTA,
        self::STATUS_GANADA,
        self::STATUS_PERDIDA,
    ];

    /**
     * Estados que se consideran cerrados: ya no cuentan como pendientes de
     * atención ni disparan recordatorios.
     */
    public const CLOSED_STATUSES = [
        self::STATUS_GANADA,
        self::STATUS_PERDIDA,
        self::STATUS_CANCELADA,
        self::STATUS_DUPLICADA,
    ];

    /**
     * Corte operativo: la operación diaria (pantallas, asignación, reportes)
     * solo debe ver cotizaciones desde esta fecha en adelante. No borra ni
     * oculta datos a nivel de base — es un filtro explícito que cada
     * controlador de pantalla/reporte aplica con scopeOperationalVisible().
     * Deliberadamente NO es un global scope: el importador, el historial por
     * teléfono (AssignmentController) y cualquier proceso interno deben poder
     * seguir viendo/usando el histórico completo sin que este corte los
     * afecte por accidente.
     *
     * Configurable por CRM_OPERATIONAL_START_DATE en .env (ver
     * config/crm.php) — Fase 6 (Concreplus): reemplaza la fecha fija
     * '2026-07-18' heredada de PROCON, que ocultaba prácticamente todo el
     * histórico real importado de Concreplus (feb-jul 2026).
     */
    public static function operationalStartDate(): string
    {
        return config('crm.operational_start_date');
    }

    public function scopeOperationalVisible(Builder $query): Builder
    {
        return $query->whereRaw(
            'COALESCE(registered_at, created_at) >= ?',
            [self::operationalStartDate().' 00:00:00']
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'imported_at' => 'datetime',
            'first_attention_at' => 'datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class)->where('is_current', true);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(QuotationNotification::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Regla de propiedad para seguimiento: una vendedora solo puede agregar
     * seguimiento a sus propias cotizaciones asignadas; admin y coordinadora
     * (o cualquier otro rol con el permiso) pueden hacerlo en cualquiera.
     */
    public function canReceiveFollowUpFrom(User $user): bool
    {
        if (! $user->can('quotations.add.followup')) {
            return false;
        }

        if ($user->hasRole('vendedora')) {
            return $this->currentAssignment?->assigned_to_user_id === $user->id;
        }

        return true;
    }

    /**
     * Pendiente de atención = tiene asignación vigente, no está en un estado
     * cerrado, y la vendedora actualmente asignada no ha registrado ningún
     * seguimiento desde que se le asignó. Usado tanto por el Dashboard como
     * por el comando de recordatorios, para no duplicar la regla.
     */
    public function isPendingAttention(): bool
    {
        if (in_array($this->crm_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        $assignment = $this->currentAssignment;

        if (! $assignment) {
            return false;
        }

        $attended = $this->followUps()
            ->where('user_id', $assignment->assigned_to_user_id)
            ->where('created_at', '>=', $assignment->assigned_at)
            ->exists();

        return ! $attended;
    }
}
