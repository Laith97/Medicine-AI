<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment;
use App\Models\User;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'refills',
        'route',
        'form',
        'instructions',
        'indication',
        'start_date',
        'generic_allowed',
        'notes',
        'ai_suggestions',
        'ai_risk_flags',
    ];

    protected $casts = [
        'start_date' => 'date',
        'generic_allowed' => 'boolean',
        'ai_suggestions' => 'array',
        'ai_risk_flags' => 'array',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Check if the prescription is still active (not expired)
     */
    public function isActive()
    {
        $endDate = $this->getEndDate();
        return $endDate && $endDate->isFuture();
    }

    /**
     * Get the calculated end date of the prescription
     */
    public function getEndDate()
    {
        if (!$this->duration) {
            return null;
        }

        // Parse duration string (e.g., "7 days", "2 weeks", "1 month")
        $duration = strtolower(trim($this->duration));

        // Extract number and unit
        if (preg_match('/(\d+)\s*(day|week|month|year)s?/', $duration, $matches)) {
            $number = (int) $matches[1];
            $unit = $matches[2];

            $carbon = $this->created_at->copy();

            switch ($unit) {
                case 'day':
                    return $carbon->addDays($number);
                case 'week':
                    return $carbon->addWeeks($number);
                case 'month':
                    return $carbon->addMonths($number);
                case 'year':
                    return $carbon->addYears($number);
            }
        }

        // If duration format is not recognized, assume it's active for 30 days as fallback
        return $this->created_at->copy()->addDays(30);
    }

    /**
     * Get active prescriptions for a patient
     */
    public static function getActiveForPatient($patientId)
    {
        return self::where('patient_id', $patientId)
            ->get()
            ->filter(function ($prescription) {
                return $prescription->isActive();
            });
    }
}