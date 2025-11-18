<?php

namespace App\Services;

use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HEPDataExportService
{
    protected $complianceService;

    public function __construct(HEPComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Export HEP data for research purposes with anonymization
     */
    public function exportForResearch(
        User $user,
        array $filters = [],
        string $format = 'csv',
        bool $anonymize = true
    ): array {
        // Check export permissions
        if (!$this->complianceService->canExportData($user, null)) {
            throw new \Exception('User does not have permission to export HEP data');
        }

        $data = $this->gatherExportData($filters);

        if ($anonymize) {
            $data = $this->anonymizeData($data);
        }

        return $this->formatAndExport($data, $format, 'research_export_' . now()->format('Y-m-d_H-i-s'));
    }

    /**
     * Export HEP data for insurance purposes
     */
    public function exportForInsurance(
        User $user,
        int $patientId,
        string $format = 'csv'
    ): array {
        // Check export permissions
        $assignment = HepAssignment::where('patient_id', $patientId)->first();
        if (!$assignment || !$this->complianceService->canExportData($user, $assignment)) {
            throw new \Exception('User does not have permission to export this patient\'s HEP data');
        }

        $data = $this->gatherPatientData($patientId);
        $data = $this->sanitizeForInsurance($data);

        return $this->formatAndExport($data, $format, 'insurance_export_' . $patientId . '_' . now()->format('Y-m-d_H-i-s'));
    }

    /**
     * Gather data for export based on filters
     */
    private function gatherExportData(array $filters = []): Collection
    {
        $query = HepProgram::with([
            'diagnosis',
            'doctor',
            'patient',
            'hepAssignments.hepProgress.hepExercise.exercise'
        ]);

        // Apply filters
        if (isset($filters['hospital_id'])) {
            $query->whereHas('patient', function ($q) use ($filters) {
                $q->where('hospital_id', $filters['hospital_id']);
            });
        }

        if (isset($filters['diagnosis_id'])) {
            $query->where('diagnosis_id', $filters['diagnosis_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $programs = $query->get();

        $exportData = collect();

        foreach ($programs as $program) {
            foreach ($program->hepAssignments as $assignment) {
                foreach ($assignment->hepProgress as $progress) {
                    $exportData->push([
                        'program_id' => $program->id,
                        'program_title' => $program->title,
                        'program_duration_weeks' => $program->duration_weeks,
                        'program_frequency_per_week' => $program->frequency_per_week,
                        'program_goals' => $program->goals,
                        'program_precautions' => $program->precautions,
                        'program_status' => $program->status,
                        'diagnosis_condition' => $program->diagnosis->condition_name ?? '',
                        'diagnosis_icd_code' => $program->diagnosis->icd_code ?? '',
                        'doctor_name' => $program->doctor->user->name,
                        'doctor_specialty' => $program->doctor->specialty->name ?? '',
                        'patient_id' => $program->patient_id,
                        'patient_age' => $program->patient->date_of_birth ?
                            Carbon::parse($program->patient->date_of_birth)->age : null,
                        'patient_gender' => $program->patient->gender ?? '',
                        'assignment_id' => $assignment->id,
                        'assignment_assigned_at' => $assignment->assigned_at,
                        'assignment_due_date' => $assignment->due_date,
                        'assignment_completion_status' => $assignment->completion_status,
                        'assignment_patient_notes' => $assignment->patient_notes,
                        'progress_date' => $progress->date,
                        'progress_completed_sets' => $progress->completed_sets,
                        'progress_completed_reps' => $progress->completed_reps,
                        'progress_duration_completed' => $progress->duration_completed,
                        'progress_pain_level' => $progress->pain_level,
                        'progress_difficulty_rating' => $progress->difficulty_rating,
                        'progress_notes' => $progress->notes,
                        'exercise_name' => $progress->hepExercise->exercise->name ?? '',
                        'exercise_week_number' => $progress->hepExercise->week_number,
                        'exercise_order' => $progress->hepExercise->order,
                        'exercise_sets' => $progress->hepExercise->sets,
                        'exercise_reps' => $progress->hepExercise->reps,
                        'exercise_duration_seconds' => $progress->hepExercise->duration_seconds,
                        'created_at' => $program->created_at,
                        'updated_at' => $program->updated_at,
                    ]);
                }
            }
        }

        return $exportData;
    }

    /**
     * Gather data for a specific patient
     */
    private function gatherPatientData(int $patientId): Collection
    {
        return $this->gatherExportData(['patient_id' => $patientId]);
    }

    /**
     * Anonymize data for research purposes
     */
    private function anonymizeData(Collection $data): Collection
    {
        $anonymizedMappings = [];

        return $data->map(function ($record) use (&$anonymizedMappings) {
            // Create anonymized patient ID
            if (!isset($anonymizedMappings[$record['patient_id']])) {
                $anonymizedMappings[$record['patient_id']] = 'P' . str_pad(count($anonymizedMappings) + 1, 6, '0', STR_PAD_LEFT);
            }

            // Create anonymized doctor ID
            $doctorKey = 'D' . $record['doctor_name'];
            if (!isset($anonymizedMappings[$doctorKey])) {
                $doctorCount = count(array_filter(array_keys($anonymizedMappings), function($k) {
                    return strpos($k, 'D') === 0;
                }));
                $anonymizedMappings[$doctorKey] = 'D' . str_pad($doctorCount + 1, 4, '0', STR_PAD_LEFT);
            }

            return [
                'anonymized_patient_id' => $anonymizedMappings[$record['patient_id']],
                'patient_age_group' => $this->getAgeGroup($record['patient_age']),
                'patient_gender' => $record['patient_gender'],
                'anonymized_doctor_id' => $anonymizedMappings[$doctorKey],
                'doctor_specialty' => $record['doctor_specialty'],
                'diagnosis_condition' => $record['diagnosis_condition'],
                'diagnosis_icd_code' => $record['diagnosis_icd_code'],
                'program_duration_weeks' => $record['program_duration_weeks'],
                'program_frequency_per_week' => $record['program_frequency_per_week'],
                'program_status' => $record['program_status'],
                'assignment_completion_status' => $record['assignment_completion_status'],
                'progress_date' => $record['progress_date'],
                'progress_completed_sets' => $record['progress_completed_sets'],
                'progress_completed_reps' => $record['progress_completed_reps'],
                'progress_duration_completed' => $record['progress_duration_completed'],
                'progress_pain_level' => $record['progress_pain_level'],
                'progress_difficulty_rating' => $record['progress_difficulty_rating'],
                'exercise_name' => $record['exercise_name'],
                'exercise_week_number' => $record['exercise_week_number'],
                'exercise_sets' => $record['exercise_sets'],
                'exercise_reps' => $record['exercise_reps'],
                'exercise_duration_seconds' => $record['exercise_duration_seconds'],
                'program_created_at' => $record['created_at'],
            ];
        });
    }

    /**
     * Sanitize data for insurance purposes
     */
    private function sanitizeForInsurance(Collection $data): Collection
    {
        return $data->map(function ($record) {
            // Remove sensitive information not needed for insurance
            unset($record['progress_notes']);
            unset($record['assignment_patient_notes']);
            unset($record['program_goals']);
            unset($record['program_precautions']);

            return $record;
        });
    }

    /**
     * Format and export data
     */
    private function formatAndExport(Collection $data, string $format, string $filename): array
    {
        switch ($format) {
            case 'csv':
                return $this->exportAsCsv($data, $filename);
            case 'json':
                return $this->exportAsJson($data, $filename);
            case 'xml':
                return $this->exportAsXml($data, $filename);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }

    /**
     * Export as CSV
     */
    private function exportAsCsv(Collection $data, string $filename): array
    {
        if ($data->isEmpty()) {
            return [
                'filename' => $filename . '.csv',
                'path' => null,
                'size' => 0,
                'records' => 0
            ];
        }

        $csvContent = $this->arrayToCsv($data->toArray());

        $path = 'exports/' . $filename . '.csv';
        Storage::put($path, $csvContent);

        return [
            'filename' => $filename . '.csv',
            'path' => $path,
            'size' => strlen($csvContent),
            'records' => $data->count(),
            'url' => Storage::url($path)
        ];
    }

    /**
     * Export as JSON
     */
    private function exportAsJson(Collection $data, string $filename): array
    {
        $jsonContent = $data->toJson(JSON_PRETTY_PRINT);

        $path = 'exports/' . $filename . '.json';
        Storage::put($path, $jsonContent);

        return [
            'filename' => $filename . '.json',
            'path' => $path,
            'size' => strlen($jsonContent),
            'records' => $data->count(),
            'url' => Storage::url($path)
        ];
    }

    /**
     * Export as XML
     */
    private function exportAsXml(Collection $data, string $filename): array
    {
        $xmlContent = $this->arrayToXml($data->toArray());

        $path = 'exports/' . $filename . '.xml';
        Storage::put($path, $xmlContent);

        return [
            'filename' => $filename . '.xml',
            'path' => $path,
            'size' => strlen($xmlContent),
            'records' => $data->count(),
            'url' => Storage::url($path)
        ];
    }

    /**
     * Convert array to CSV string
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) return '';

        $headers = array_keys($data[0]);
        $csv = implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', (string)$value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Convert array to XML string
     */
    private function arrayToXml(array $data): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<hep_export>' . "\n";

        foreach ($data as $record) {
            $xml .= '  <record>' . "\n";
            foreach ($record as $key => $value) {
                $xml .= '    <' . $key . '>' . htmlspecialchars((string)$value) . '</' . $key . '>' . "\n";
            }
            $xml .= '  </record>' . "\n";
        }

        $xml .= '</hep_export>';

        return $xml;
    }

    /**
     * Get age group for anonymization
     */
    private function getAgeGroup(?int $age): string
    {
        if (!$age) return 'Unknown';

        if ($age < 18) return 'Under 18';
        if ($age < 30) return '18-29';
        if ($age < 40) return '30-39';
        if ($age < 50) return '40-49';
        if ($age < 60) return '50-59';
        if ($age < 70) return '60-69';
        return '70+';
    }

    /**
     * Get available export formats
     */
    public static function getAvailableFormats(): array
    {
        return ['csv', 'json', 'xml'];
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 30): int
    {
        $files = Storage::files('exports');
        $deleted = 0;

        foreach ($files as $file) {
            $filePath = Storage::path($file);
            if (filemtime($filePath) < now()->subDays($daysOld)->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }

        Log::info("Cleaned up {$deleted} old HEP export files");

        return $deleted;
    }

    /**
     * Validate export request
     */
    public function validateExportRequest(array $filters, string $format): array
    {
        $errors = [];

        if (!in_array($format, self::getAvailableFormats())) {
            $errors[] = 'Invalid export format. Available formats: ' . implode(', ', self::getAvailableFormats());
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $from = Carbon::parse($filters['date_from']);
            $to = Carbon::parse($filters['date_to']);

            if ($from->gt($to)) {
                $errors[] = 'Date from cannot be after date to';
            }

            if ($from->diffInDays($to) > 365) {
                $errors[] = 'Date range cannot exceed 1 year for performance reasons';
            }
        }

        return $errors;
    }
}
