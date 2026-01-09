<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientMonitoringSession extends Model
{
    use HasFactory;

    protected $table = 'clinical_monitoring_sessions';

    protected $fillable = [
        'patient_id',
        'status',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(ClinicalIndicator::class, 'session_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
