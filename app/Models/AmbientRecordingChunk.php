<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbientRecordingChunk extends Model
{
    public $timestamps = false; // custom created_at only

    protected $fillable = [
        'session_id',
        'chunk_data',
        'duration',
        'recorded_at',
        'processed_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AmbientRecordingSession::class, 'session_id');
    }
}
