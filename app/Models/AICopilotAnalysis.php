<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AICopilotAnalysis extends Model
{
    use HasFactory;

    protected $table = 'ai_copilot_analyses';

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'analysis_data',
        'generated_at',
        'summary',
        'considerations',
        'questions',
        'red_flags',
        'status',
        'reviewed_by_doctor',
        'reviewed_at',
        'doctor_notes',
        // Guest patient fields
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_date_of_birth',
        'guest_gender',
        'guest_address'
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'generated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'considerations' => 'array',
        'questions' => 'array',
        'red_flags' => 'array',
        'guest_date_of_birth' => 'date'
    ];

    /**
     * Get the appointment associated with this AI analysis
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient associated with this AI analysis
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor who generated this AI analysis
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the doctor who reviewed this analysis
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_doctor');
    }

    /**
     * Scope for active analyses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for reviewed analyses
     */
    public function scopeReviewed($query)
    {
        return $query->whereNotNull('reviewed_by_doctor');
    }

    /**
     * Scope for analyses that need review
     */
    public function scopeNeedsReview($query)
    {
        return $query->whereNull('reviewed_by_doctor');
    }

    /**
     * Scope for analyses by appointment
     */
    public function scopeForAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    /**
     * Scope for analyses by patient
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for analyses by doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for recent analyses
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    /**
     * Check if analysis is reviewed
     */
    public function isReviewed()
    {
        return !is_null($this->reviewed_by_doctor);
    }

    /**
     * Mark analysis as reviewed
     */
    public function markAsReviewed($doctorId, $notes = null)
    {
        $this->update([
            'reviewed_by_doctor' => $doctorId,
            'reviewed_at' => now(),
            'doctor_notes' => $notes
        ]);
    }

    /**
     * Get formatted generated date
     */
    public function getFormattedGeneratedDateAttribute()
    {
        return $this->generated_at->format('M j, Y g:i A');
    }

    /**
     * Get formatted reviewed date
     */
    public function getFormattedReviewedDateAttribute()
    {
        return $this->reviewed_at ? $this->reviewed_at->format('M j, Y g:i A') : null;
    }

    /**
     * Get considerations as formatted string
     */
    public function getConsiderationsFormattedAttribute()
    {
        if (empty($this->considerations)) {
            return 'No considerations identified';
        }

        return implode('; ', $this->considerations);
    }

    /**
     * Get red flags as formatted string
     */
    public function getRedFlagsFormattedAttribute()
    {
        if (empty($this->red_flags)) {
            return 'No red flags detected';
        }

        return implode('; ', $this->red_flags);
    }

    /**
     * Check if this analysis is for a guest patient
     */
    public function isGuestAnalysis()
    {
        return is_null($this->patient_id) && !empty($this->guest_email);
    }

    /**
     * Get patient name (registered or guest)
     */
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->name : $this->guest_name;
    }

    /**
     * Get patient email (registered or guest)
     */
    public function getPatientEmailAttribute()
    {
        return $this->patient ? $this->patient->email : $this->guest_email;
    }

    /**
     * Get patient phone (registered or guest)
     */
    public function getPatientPhoneAttribute()
    {
        return $this->patient ? $this->patient->phone : $this->guest_phone;
    }

    /**
     * Scope for guest analyses
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('patient_id')->whereNotNull('guest_email');
    }

    /**
     * Scope for registered patient analyses
     */
    public function scopeRegistered($query)
    {
        return $query->whereNotNull('patient_id');
    }

    /**
     * Scope for analyses by guest email
     */
    public function scopeByGuestEmail($query, $email)
    {
        return $query->where('guest_email', $email);
    }
}