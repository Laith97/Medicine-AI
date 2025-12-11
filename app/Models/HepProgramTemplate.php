<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HepProgramTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'diagnosis_type',
        'duration_weeks',
        'frequency_per_week',
        'goals',
        'precautions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'frequency_per_week' => 'integer',
        'goals' => 'array',
        'precautions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this template
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the template exercises
     */
    public function templateExercises()
    {
        return $this->hasMany(HepTemplateExercise::class);
    }

    /**
     * Get programs created from this template
     */
    public function programs()
    {
        return $this->hasMany(HepProgram::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for templates by diagnosis type
     */
    public function scopeByDiagnosisType($query, $diagnosisType)
    {
        return $query->where('diagnosis_type', $diagnosisType);
    }

    /**
     * Get template categories
     */
    public static function getCategories()
    {
        return [
            'orthopedic',
            'neurological',
            'cardiovascular',
            'sports_medicine',
            'geriatric',
            'pediatric',
            'post-surgical',
            'chronic_pain',
            'general_fitness',
        ];
    }

    /**
     * Get diagnosis types
     */
    public static function getDiagnosisTypes()
    {
        return [
            'knee_osteoarthritis',
            'hip_osteoarthritis',
            'shoulder_impingement',
            'low_back_pain',
            'neck_pain',
            'ankle_sprain',
            'acl_reconstruction',
            'total_knee_replacement',
            'total_hip_replacement',
            'rotator_cuff_repair',
            'stroke',
            'parkinsons',
            'multiple_sclerosis',
            'spinal_cord_injury',
            'heart_disease',
            'copd',
            'diabetes',
            'general_weakness',
            'balance_disorders',
            'sports_injury',
            'tendonitis',
            'fracture_recovery',
            'other',
        ];
    }

    /**
     * Create a program from this template
     */
    public function createProgram(User $doctor, User $patient, Diagnosis $diagnosis, array $customizations = [])
    {
        $programData = [
            'title' => $customizations['title'] ?? $this->name . ' Program',
            'description' => $customizations['description'] ?? $this->description,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
            'duration_weeks' => $customizations['duration_weeks'] ?? $this->duration_weeks,
            'frequency_per_week' => $customizations['frequency_per_week'] ?? $this->frequency_per_week,
            'goals' => $customizations['goals'] ?? $this->goals,
            'precautions' => $customizations['precautions'] ?? $this->precautions,
            'status' => 'active',
            'template_id' => $this->id,
        ];

        $program = HepProgram::create($programData);

        // Create program exercises from template
        foreach ($this->templateExercises()->orderBy('week_number')->orderBy('order')->get() as $templateExercise) {
            HepExercise::create([
                'hep_program_id' => $program->id,
                'exercise_id' => $templateExercise->exercise_id,
                'sets' => $templateExercise->sets,
                'reps' => $templateExercise->reps,
                'duration_seconds' => $templateExercise->duration_seconds,
                'rest_seconds' => $templateExercise->rest_seconds,
                'frequency' => $templateExercise->frequency,
                'progression_notes' => $templateExercise->progression_notes,
                'week_number' => $templateExercise->week_number,
                'order' => $templateExercise->order,
            ]);
        }

        return $program;
    }

    /**
     * Get usage count
     */
    public function getUsageCount()
    {
        return $this->programs()->count();
    }

    /**
     * Check if template is safe for patient
     */
    public function isSafeForPatient(User $patient, array $additionalConditions = [])
    {
        // Get patient's conditions from diagnosis or medical history
        $patientConditions = [];

        // Add conditions from diagnosis if available
        if ($patient->patientDiagnoses()->exists()) {
            $latestDiagnosis = $patient->patientDiagnoses()->latest()->first();
            if ($latestDiagnosis && $latestDiagnosis->diagnosis) {
                $patientConditions[] = $latestDiagnosis->diagnosis->name;
            }
        }

        // Add additional conditions
        $patientConditions = array_merge($patientConditions, $additionalConditions);

        // Check if any exercises in template have contraindications for patient
        foreach ($this->templateExercises as $templateExercise) {
            if ($templateExercise->exercise && !$templateExercise->exercise->isSafeForPatient($patientConditions)) {
                return false;
            }
        }

        return true;
    }
}
