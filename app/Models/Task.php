<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'title', 'description', 'category',
        'priority', 'status', 'assigned_to', 'due_at', 'evidence',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}