<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'facade_request_id',
    'user_id',
    'event_type',
    'metadata',
])]
class FacadeRequestEvent extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_AUTHORIZATION_EMAIL_SENT = 'authorization_email_sent';

    public const EVENT_APPROVED = 'approved';

    public const EVENT_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function facadeRequest(): BelongsTo
    {
        return $this->belongsTo(FacadeRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
