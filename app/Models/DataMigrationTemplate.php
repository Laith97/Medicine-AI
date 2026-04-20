<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMigrationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'entity_type',
        'field_mapping',
        'validation_rules',
        'transform_rules',
        'created_by',
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'validation_rules' => 'array',
        'transform_rules' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getEntityTypeOptions(): array
    {
        return [
            'department' => 'Department',
            'specialty' => 'Specialty',
            'doctor' => 'Doctor',
            'patient' => 'Patient',
            'appointment' => 'Appointment',
            'diagnosis' => 'Diagnosis',
            'prescription' => 'Prescription',
            'treatment' => 'Treatment',
            'allergy' => 'Allergy',
            'insurance' => 'Insurance',
            'user' => 'User',
            'setting' => 'Setting',
        ];
    }
}