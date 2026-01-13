<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AICopilotUsageLog extends Model
{
    use HasFactory;

    protected $table = 'ai_copilot_usage_logs';

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'user_id',
        'request_data',
        'response_data',
        'status',
        'requested_at',
        'completed_at',
        'error_message'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    /**
     * Get the appointment associated with the AI copilot usage
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient associated with the AI copilot usage
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor associated with the AI copilot usage
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the user who requested the AI copilot analysis
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include requests for a specific appointment
     */
    public function scopeForAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    /**
     * Scope a query to only include requests for a specific patient
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope a query to only include requests by a specific doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}