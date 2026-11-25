<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $fillable = [
        'project_id', 'task_id', 'ticket_no', 'title',
        'description', 'severity', 'status', 'resolution_notes',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}