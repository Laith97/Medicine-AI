<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'patient_id',
        'doctor_id',
        'action',
        'model_type',
        'model_id',
        'metadata',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Create a new audit log entry
     */
    public static function log($action, $userId = null, $patientId = null, $doctorId = null, $metadata = [])
    {
        return self::create([
            'user_id' => $userId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'action' => $action,
            'metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Get the badge class for the action type
     */
    public function getActionBadgeClass()
    {
        switch ($this->action) {
            case 'doctor_assigned':
            case 'patient_assigned':
            case 'diagnosis_created':
            case 'appointment_booked':
                return 'success';
            case 'admin_impersonation_started':
            case 'hospital_admin_impersonation_started':
                return 'warning';
            case 'unauthorized_patient_access':
            case 'unauthorized_diagnosis_access':
            case 'unauthorized_appointment_access':
                return 'danger';
            case 'admin_impersonation_ended':
            case 'hospital_admin_impersonation_ended':
                return 'info';
            default:
                return 'secondary';
        }
    }
}
