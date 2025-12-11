<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'difficulty_level',
        'instructions',
        'video_url',
        'image_url',
        'contraindications',
        'equipment_required',
        'target_muscle_groups',
        'duration',
    ];

    protected $casts = [
        'contraindications' => 'array',
        'equipment_required' => 'array',
        'target_muscle_groups' => 'array',
        'duration' => 'integer',
    ];

    /**
     * Get the HEP exercises that use this exercise
     */
    public function hepExercises()
    {
        return $this->hasMany(HepExercise::class);
    }

    /**
     * Scope for exercises by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for exercises by difficulty level
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope for exercises suitable for a patient based on contraindications
     */
    public function scopeSafeForPatient($query, array $patientConditions = [])
    {
        if (empty($patientConditions)) {
            return $query;
        }

        return $query->where(function ($q) use ($patientConditions) {
            foreach ($patientConditions as $condition) {
                $q->whereJsonDoesntContain('contraindications', $condition);
            }
        });
    }

    /**
     * Check if exercise is safe for patient based on conditions
     */
    public function isSafeForPatient(array $patientConditions = [])
    {
        if (empty($patientConditions) || empty($this->contraindications)) {
            return true;
        }

        return empty(array_intersect($patientConditions, $this->contraindications));
    }

    /**
     * Get exercise categories
     */
    public static function getCategories()
    {
        return [
            'cardiovascular',
            'strength',
            'flexibility',
            'balance',
            'posture',
            'functional',
            'sports_specific',
        ];
    }

    /**
     * Get difficulty levels
     */
    public static function getDifficultyLevels()
    {
        return ['beginner', 'intermediate', 'advanced'];
    }

    /**
     * Quality assurance checks
     */
    public function getQualityScore()
    {
        $score = 0;
        $maxScore = 100;

        // Required fields (40 points)
        if (!empty($this->name)) $score += 10;
        if (!empty($this->description) && strlen($this->description) > 20) $score += 10;
        if (!empty($this->instructions) && strlen($this->instructions) > 50) $score += 10;
        if (!empty($this->category)) $score += 10;

        // Media content (20 points)
        if (!empty($this->video_url) || !empty($this->image_url)) $score += 10;
        if (!empty($this->video_url) && !empty($this->image_url)) $score += 10;

        // Detailed information (20 points)
        if (!empty($this->target_muscle_groups) && count($this->target_muscle_groups) > 0) $score += 5;
        if (!empty($this->equipment_required)) $score += 5;
        if (!empty($this->contraindications) && count($this->contraindications) > 0) $score += 5;
        if (!empty($this->duration)) $score += 5;

        // Usage and validation (20 points)
        if ($this->hepExercises()->count() > 0) $score += 10; // Used in programs
        if ($this->isSafeForPatient([])) $score += 10; // Basic safety check

        return min($score, $maxScore);
    }

    /**
     * Get quality issues
     */
    public function getQualityIssues()
    {
        $issues = [];

        if (empty($this->name)) {
            $issues[] = 'Missing exercise name';
        }

        if (empty($this->description) || strlen($this->description) < 20) {
            $issues[] = 'Description too short or missing (should be at least 20 characters)';
        }

        if (empty($this->instructions) || strlen($this->instructions) < 50) {
            $issues[] = 'Instructions too brief or missing (should be at least 50 characters)';
        }

        if (empty($this->category)) {
            $issues[] = 'Missing category';
        }

        if (empty($this->video_url) && empty($this->image_url)) {
            $issues[] = 'No media content (video or image recommended)';
        }

        if (empty($this->target_muscle_groups) || count($this->target_muscle_groups) === 0) {
            $issues[] = 'No target muscle groups specified';
        }

        if (empty($this->contraindications) || count($this->contraindications) === 0) {
            $issues[] = 'No contraindications specified (safety concern)';
        }

        if (empty($this->duration)) {
            $issues[] = 'No duration specified';
        }

        return $issues;
    }

    /**
     * Check if exercise meets quality standards
     */
    public function meetsQualityStandards()
    {
        return $this->getQualityScore() >= 70;
    }

    /**
     * Get quality status
     */
    public function getQualityStatus()
    {
        $score = $this->getQualityScore();

        if ($score >= 90) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 50) return 'fair';
        return 'poor';
    }

    /**
     * Get quality status color
     */
    public function getQualityStatusColor()
    {
        return match($this->getQualityStatus()) {
            'excellent' => 'success',
            'good' => 'primary',
            'fair' => 'warning',
            'poor' => 'danger',
        };
    }
}
