<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content',
        'provider', 'model_name', 'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}