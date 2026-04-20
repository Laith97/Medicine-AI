<?php

namespace App\Jobs;

use App\Models\DataMigration;
use App\Models\DataMigrationIdMapping;
use App\Models\DataMigrationRecord;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\Allergy;
use App\Models\Insurance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessDataMigration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public DataMigration $migration;
    public int $batchSize = 100;

    public function __construct(DataMigration $migration)
    {
        $this->migration = $migration;
    }

    public function handle(): void
    {
        try {
            $this->migration->markAsInProgress();

            // Parse source file
            $filePath = Storage::path($this->migration->source_path);
            $data = $this->parseFile($filePath);

            if (empty($data)) {
                $this->migration->markAsFailed('No data found in file');
                return;
            }

            $this->migration->update(['total_records' => count($data)]);

            // Process in batches
            $fieldMapping = $this->migration->field_mapping ?? [];
            $processed = 0;
            $success = 0;
            $failed = 0;

            foreach (array_chunk($data, $this->batchSize) as $batch) {
                foreach ($batch as $row) {
                    $result = $this->processRow($row, $fieldMapping);
                    if ($result['success']) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    $processed++;
                }

                // Update progress
                $this->migration->update([
                    'processed_records' => $processed,
                    'success_records' => $success,
                    'failed_records' => $failed,
                ]);
            }

            $this->migration->markAsCompleted();

        } catch (\Exception $e) {
            Log::error('Data migration failed', [
                'migration_id' => $this->migration->id,
                'error' => $e->getMessage(),
            ]);
            $this->migration->markAsFailed($e->getMessage());
        }
    }

    private function parseFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->parseCsv($filePath);
        } else {
            return $this->parseExcel($filePath);
        }
    }

    private function parseCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        return $data;
    }

    private function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow());

        if (empty($rows)) return [];

        $headers = array_map(fn($h) => trim((string)$h), $rows[0]);
        $data = [];

        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) === count($headers)) {
                $data[] = array_combine($headers, $rows[$i]);
            }
        }

        return $data;
    }

    private function processRow(array $row, array $fieldMapping): array
    {
        $record = new DataMigrationRecord();
        $record->data_migration_id = $this->migration->id;
        $record->entity_type = $this->migration->entity_type;
        $record->source_data = $row;
        $record->status = DataMigrationRecord::STATUS_PENDING;
        $record->source_id = $row[$fieldMapping['source_id'] ?? ''] ?? null;

        try {
            // Apply field mapping
            $mappedData = $this->applyFieldMapping($row, $fieldMapping);
            $record->transformed_data = $mappedData;
            $record->status = DataMigrationRecord::STATUS_MAPPED;

            // Validate
            $validationResult = $this->validateData($mappedData, $record->entity_type);
            if (!$validationResult['valid']) {
                $record->validation_errors = $validationResult['errors'];
                $record->status = DataMigrationRecord::STATUS_FAILED;
                $record->error_message = implode('; ', array_column($validationResult['errors'], 'message'));
                $record->save();
                return ['success' => false, 'errors' => $validationResult['errors']];
            }
            $record->status = DataMigrationRecord::STATUS_VALIDATED;

            // Import
            $medcuraId = $this->importData($mappedData, $record->entity_type);
            $record->medcura_id = $medcuraId;
            $record->status = DataMigrationRecord::STATUS_IMPORTED;

            // Create ID mapping
            if ($medcuraId) {
                DataMigrationIdMapping::createMapping(
                    $this->migration->id,
                    $record->entity_type,
                    $record->source_id ?? '',
                    $this->getMedCuraModelType($record->entity_type),
                    $medcuraId
                );
            }

            $record->save();
            return ['success' => true, 'medcura_id' => $medcuraId];

        } catch (\Exception $e) {
            $record->status = DataMigrationRecord::STATUS_FAILED;
            $record->error_message = $e->getMessage();
            $record->save();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function applyFieldMapping(array $row, array $fieldMapping): array
    {
        $mapped = [];
        foreach ($fieldMapping as $sourceField => $targetField) {
            if ($sourceField === 'source_id') continue;
            $mapped[$targetField] = $row[$sourceField] ?? null;
        }
        return $mapped;
    }

    private function validateData(array $data, string $entityType): array
    {
        $errors = [];
        $rules = $this->getValidationRules($entityType);

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            // Required check
            if ($rule['required'] && empty($value)) {
                $errors[] = ['field' => $field, 'message' => "{$field} is required"];
                continue;
            }

            if ($value) {
                // Email format
                if ($rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['field' => $field, 'message' => "{$field} must be a valid email"];
                }

                // Date format
                if ($rule['type'] === 'date' && !strtotime($value)) {
                    $errors[] = ['field' => $field, 'message' => "{$field} must be a valid date"];
                }

                // Phone format
                if ($rule['type'] === 'phone') {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleanPhone) < 10) {
                        $errors[] = ['field' => $field, 'message' => "{$field} must be at least 10 digits"];
                    }
                }

                // Numeric
                if ($rule['type'] === 'numeric' && !is_numeric($value)) {
                    $errors[] = ['field' => $field, 'message' => "{$field} must be a number"];
                }
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function getValidationRules(string $entityType): array
    {
        return match($entityType) {
            'department' => [
                'name' => ['required' => true, 'type' => 'text'],
                'description' => ['required' => false, 'type' => 'text'],
            ],
            'specialty' => [
                'name' => ['required' => true, 'type' => 'text'],
            ],
            'doctor' => [
                'first_name' => ['required' => true, 'type' => 'text'],
                'last_name' => ['required' => true, 'type' => 'text'],
                'email' => ['required' => true, 'type' => 'email'],
                'phone' => ['required' => false, 'type' => 'phone'],
                'specialty' => ['required' => false, 'type' => 'text'],
            ],
            'patient' => [
                'first_name' => ['required' => true, 'type' => 'text'],
                'last_name' => ['required' => true, 'type' => 'text'],
                'email' => ['required' => true, 'type' => 'email'],
                'phone' => ['required' => false, 'type' => 'phone'],
                'date_of_birth' => ['required' => false, 'type' => 'date'],
            ],
            'appointment' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'doctor_id' => ['required' => true, 'type' => 'text'],
                'appointment_date' => ['required' => true, 'type' => 'date'],
                'appointment_time' => ['required' => false, 'type' => 'text'],
            ],
            'diagnosis' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'icd_code' => ['required' => true, 'type' => 'text'],
                'description' => ['required' => false, 'type' => 'text'],
            ],
            'prescription' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'doctor_id' => ['required' => true, 'type' => 'text'],
                'medication_name' => ['required' => true, 'type' => 'text'],
            ],
            'treatment' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'doctor_id' => ['required' => true, 'type' => 'text'],
                'treatment_name' => ['required' => true, 'type' => 'text'],
            ],
            'allergy' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'allergen' => ['required' => true, 'type' => 'text'],
            ],
            'insurance' => [
                'patient_id' => ['required' => true, 'type' => 'text'],
                'provider_name' => ['required' => true, 'type' => 'text'],
                'policy_number' => ['required' => true, 'type' => 'text'],
            ],
            default => [],
        };
    }

    private function importData(array $data, string $entityType): ?string
    {
        return match($entityType) {
            'department' => $this->importDepartment($data),
            'specialty' => $this->importSpecialty($data),
            'doctor' => $this->importDoctor($data),
            'patient' => $this->importPatient($data),
            'appointment' => $this->importAppointment($data),
            'diagnosis' => $this->importDiagnosis($data),
            'prescription' => $this->importPrescription($data),
            'allergy' => $this->importAllergy($data),
            'insurance' => $this->importInsurance($data),
            default => null,
        };
    }

    private function importDepartment(array $data): string
    {
        $department = Department::create([
            'name' => $data['name'] ?? 'Unknown Department',
            'description' => $data['description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'location' => $data['location'] ?? null,
        ]);
        return $department->id;
    }

    private function importSpecialty(array $data): string
    {
        $specialty = \App\Models\Specialty::create([
            'name' => $data['name'] ?? 'Unknown Specialty',
            'description' => $data['description'] ?? null,
        ]);
        return $specialty->id;
    }

    private function importDoctor(array $data): string
    {
        $user = User::create([
            'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => 'doctor',
            'password' => bcrypt(Str::random(16)),
        ]);

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'specialty_id' => $this->findSpecialtyId($data['specialty'] ?? null),
            'license_number' => $data['license_number'] ?? null,
            'npi' => $data['npi'] ?? null,
        ]);

        return $doctor->id;
    }

    private function importPatient(array $data): string
    {
        $user = User::create([
            'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => 'patient',
            'password' => bcrypt(Str::random(16)),
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
        ]);

        return $patient->id;
    }

    private function importAppointment(array $data): string
    {
        // Map source patient/doctor IDs to MedCura IDs
        $sourcePatientId = $data['patient_id'] ?? null;
        $sourceDoctorId = $data['doctor_id'] ?? null;

        $patientId = $this->resolveMedCuraId($sourcePatientId, 'patient', 'patient');
        $doctorId = $this->resolveMedCuraId($sourceDoctorId, 'doctor', 'doctor');

        if (!$patientId || !$doctorId) {
            throw new \Exception("Could not resolve patient or doctor ID");
        }

        $appointment = Appointment::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $data['appointment_date'] ?? now(),
            'appointment_time' => $data['appointment_time'] ?? '09:00',
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            'status' => $data['status'] ?? 'scheduled',
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $appointment->id;
    }

    private function importDiagnosis(array $data): string
    {
        $patientId = $this->resolveMedCuraId($data['patient_id'] ?? null, 'patient', 'patient');
        $doctorId = $this->resolveMedCuraId($data['doctor_id'] ?? null, 'doctor', 'doctor');

        $diagnosis = Diagnosis::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'icd_code' => $data['icd_code'] ?? null,
            'description' => $data['description'] ?? null,
            'diagnosis_date' => $data['diagnosis_date'] ?? now(),
            'severity' => $data['severity'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $diagnosis->id;
    }

    private function importPrescription(array $data): string
    {
        $patientId = $this->resolveMedCuraId($data['patient_id'] ?? null, 'patient', 'patient');
        $doctorId = $this->resolveMedCuraId($data['doctor_id'] ?? null, 'doctor', 'doctor');

        $prescription = Prescription::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'medication_name' => $data['medication_name'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'instructions' => $data['instructions'] ?? null,
        ]);

        return $prescription->id;
    }

    private function importAllergy(array $data): string
    {
        $patientId = $this->resolveMedCuraId($data['patient_id'] ?? null, 'patient', 'patient');

        $allergy = Allergy::create([
            'patient_id' => $patientId,
            'allergen' => $data['allergen'] ?? null,
            'reaction' => $data['reaction'] ?? null,
            'severity' => $data['severity'] ?? null,
            'diagnosed_date' => $data['diagnosed_date'] ?? null,
        ]);

        return $allergy->id;
    }

    private function importInsurance(array $data): string
    {
        $patientId = $this->resolveMedCuraId($data['patient_id'] ?? null, 'patient', 'patient');

        $insurance = Insurance::create([
            'patient_id' => $patientId,
            'provider_name' => $data['provider_name'] ?? null,
            'policy_number' => $data['policy_number'] ?? null,
            'group_number' => $data['group_number'] ?? null,
            'subscriber_name' => $data['subscriber_name'] ?? null,
            'relationship' => $data['relationship'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'expiration_date' => $data['expiration_date'] ?? null,
        ]);

        return $insurance->id;
    }

    private function resolveMedCuraId(?string $sourceId, string $sourceType, string $medcuraType): ?string
    {
        if (!$sourceId) return null;

        $mapping = DataMigrationIdMapping::where('data_migration_id', $this->migration->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        return $mapping?->medcura_id;
    }

    private function findSpecialtyId(?string $specialtyName): ?int
    {
        if (!$specialtyName) return null;

        return \App\Models\Specialty::where('name', 'like', "%{$specialtyName}%")->value('id');
    }

    private function getMedCuraModelType(string $entityType): string
    {
        return match($entityType) {
            'department' => 'App\\Models\\Department',
            'specialty' => 'App\\Models\\Specialty',
            'doctor' => 'App\\Models\\Doctor',
            'patient' => 'App\\Models\\Patient',
            'appointment' => 'App\\Models\\Appointment',
            'diagnosis' => 'App\\Models\\Diagnosis',
            'prescription' => 'App\\Models\\Prescription',
            'allergy' => 'App\\Models\\Allergy',
            'insurance' => 'App\\Models\\Insurance',
            default => 'App\\Models\\User',
        };
    }
}