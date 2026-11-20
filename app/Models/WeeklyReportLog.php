<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'report_type',
    'period_start',
    'period_end',
    'recipients',
    'sent_at',
    'status',
    'error_message',
])]
class WeeklyReportLog extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED_NO_RECIPIENTS = 'skipped_no_recipients';

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'sent_at' => 'datetime',
        ];
    }
}
