<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringDevice extends Model
{
    use HasFactory;

    protected $table = 'clinical_monitoring_devices';

    protected $fillable = [
        'device_id',
        'device_type',
        'model',
        'manufacturer',
        'status',
        'current_patient_id',
        'last_sync_at',
        'configuration',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'configuration' => 'array',
    ];

    public function currentPatient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_patient_id');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(ClinicalIndicator::class, 'device_id');
    }
}
