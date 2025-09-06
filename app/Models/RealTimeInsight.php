<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealTimeInsight extends Model
{
    public $timestamps = false; // custom created_at only

    protected $fillable = [
        'session_id',
        'insight_type',
        'insight_data',
        'confidence',
        'timestamp',
    ];

    protected $casts = [
        'insight_data' => 'array',
        'timestamp' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AmbientRecordingSession::class, 'session_id');
    }
}
