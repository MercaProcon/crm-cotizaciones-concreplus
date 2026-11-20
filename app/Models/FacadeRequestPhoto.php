<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'facade_request_id',
    'path',
    'original_name',
    'mime_type',
    'size',
])]
class FacadeRequestPhoto extends Model
{
    public function facadeRequest(): BelongsTo
    {
        return $this->belongsTo(FacadeRequest::class);
    }
}
