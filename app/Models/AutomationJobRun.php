<?php

namespace App\Models;

use App\Automation\RunStatus;
use App\Automation\TriggeredBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationJobRun extends Model
{
    protected $fillable = [
        'automation_job_id',
        'status',
        'started_at',
        'finished_at',
        'error_message',
        'response_summary',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => RunStatus::class,
            'triggered_by' => TriggeredBy::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(AutomationJob::class, 'automation_job_id');
    }
}
