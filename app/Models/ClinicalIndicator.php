<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalIndicator extends Model
{
    use HasFactory;

    protected $table = 'clinical_indicators';

    protected $fillable = [
        'patient_id',
        'session_id',
        'device_id',
        'type',
        'name',
        'value',
        'unit',
        'measured_at',
        'metadata',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PatientMonitoringSession::class, 'session_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitoringDevice::class, 'device_id');
    }
}
