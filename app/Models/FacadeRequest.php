<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'folio',
    'status',
    'submitted_at',
    'agent_user_id',
    'agent_name',
    'agent_email',
    'agent_phone',
    'zone',
    'store_name',
    'client_name',
    'client_phone',
    'manager_name',
    'manager_phone',
    'client_registered_tax',
    'street',
    'number',
    'cross_streets',
    'neighborhood',
    'city',
    'municipality',
    'state',
    'postal_code',
    'google_maps_url',
    'is_corner',
    'is_mid_block',
    'is_end_of_street',
    'near_existing_procon_facade',
    'on_main_avenue',
    'has_clear_visibility',
    'location_notes',
    'months_as_distributor',
    'estimated_monthly_consumption',
    'frequent_products',
    'additional_comments',
    'client_signature_path',
    'client_signature_name',
    'agent_signature_path',
    'agent_signature_name',
    'pdf_path',
    'authorization_token',
    'authorization_token_expires_at',
    'authorization_email_sent_at',
    'authorization_comment',
    'authorized_at',
    'rejected_at',
    'authorization_token_used_at',
    'authorizer_key',
    'authorizer_name',
    'authorizer_email',
])]
class FacadeRequest extends Model
{
    use HasFactory;

    /**
     * Formulario capturado, todavía sin firmas, PDF ni correo al
     * autorizador (fases 3-6). Es el estado inicial real de esta fase.
     */
    public const STATUS_CAPTURADA = 'capturada';

    public const STATUS_PENDIENTE_AUTORIZACION = 'pendiente_autorizacion';

    public const STATUS_AUTORIZADA = 'autorizada';

    public const STATUS_RECHAZADA = 'rechazada';

    public const STATUS_CANCELADA = 'cancelada';

    public const STATUS_EN_PRODUCCION = 'en_produccion';

    public const STATUS_INSTALADA = 'instalada';

    public const STATUSES = [
        self::STATUS_CAPTURADA,
        self::STATUS_PENDIENTE_AUTORIZACION,
        self::STATUS_AUTORIZADA,
        self::STATUS_RECHAZADA,
        self::STATUS_CANCELADA,
        self::STATUS_EN_PRODUCCION,
        self::STATUS_INSTALADA,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'authorization_token_expires_at' => 'datetime',
            'authorization_email_sent_at' => 'datetime',
            'authorized_at' => 'datetime',
            'rejected_at' => 'datetime',
            'authorization_token_used_at' => 'datetime',
            'client_registered_tax' => 'boolean',
            'is_corner' => 'boolean',
            'is_mid_block' => 'boolean',
            'is_end_of_street' => 'boolean',
            'near_existing_procon_facade' => 'boolean',
            'on_main_avenue' => 'boolean',
            'has_clear_visibility' => 'boolean',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FacadeRequestPhoto::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FacadeRequestEvent::class);
    }

    /**
     * Regla de propiedad para listado/detalle: una vendedora solo ve sus
     * propias solicitudes; admin, coordinadora (o cualquiera con
     * facades.view.all) ven todas.
     */
    public function canBeViewedBy(User $user): bool
    {
        if ($user->can('facades.view.all')) {
            return true;
        }

        return $user->can('facades.view') && $this->agent_user_id === $user->id;
    }
}
