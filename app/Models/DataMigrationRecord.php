<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMigrationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_migration_id',
        'entity_type',
        'source_id',
        'medcura_id',
        'status',
        'source_data',
        'transformed_data',
        'validation_errors',
        'error_message',
    ];

    protected $casts = [
        'source_data' => 'array',
        'transformed_data' => 'array',
        'validation_errors' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_MAPPED = 'mapped';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_IMPORTED = 'imported';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const ENTITY_DEPARTMENT = 'department';
    public const ENTITY_SPECIALTY = 'specialty';
    public const ENTITY_DOCTOR = 'doctor';
    public const ENTITY_PATIENT = 'patient';
    public const ENTITY_APPOINTMENT = 'appointment';
    public const ENTITY_DIAGNOSIS = 'diagnosis';
    public const ENTITY_PRESCRIPTION = 'prescription';
    public const ENTITY_TREATMENT = 'treatment';
    public const ENTITY_ALLERGY = 'allergy';
    public const ENTITY_INSURANCE = 'insurance';
    public const ENTITY_USER = 'user';
    public const ENTITY_SETTING = 'setting';

    public function dataMigration(): BelongsTo
    {
        return $this->belongsTo(DataMigration::class);
    }

    public function hasErrors(): bool
    {
        return $this->status === self::STATUS_FAILED && !empty($this->validation_errors);
    }

    public function getValidationErrorSummary(): ?string
    {
        if (empty($this->validation_errors)) {
            return null;
        }
        $errors = array_column($this->validation_errors, 'message');
        return implode(', ', $errors);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-secondary',
            self::STATUS_MAPPED => 'bg-info',
            self::STATUS_VALIDATED => 'bg-primary',
            self::STATUS_IMPORTED => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
            self::STATUS_SKIPPED => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    public static function getEntityTypeOptions(): array
    {
        return [
            self::ENTITY_DEPARTMENT => 'Department',
            self::ENTITY_SPECIALTY => 'Specialty',
            self::ENTITY_DOCTOR => 'Doctor',
            self::ENTITY_PATIENT => 'Patient',
            self::ENTITY_APPOINTMENT => 'Appointment',
            self::ENTITY_DIAGNOSIS => 'Diagnosis',
            self::ENTITY_PRESCRIPTION => 'Prescription',
            self::ENTITY_TREATMENT => 'Treatment',
            self::ENTITY_ALLERGY => 'Allergy',
            self::ENTITY_INSURANCE => 'Insurance',
            self::ENTITY_USER => 'User',
            self::ENTITY_SETTING => 'Setting',
        ];
    }
}