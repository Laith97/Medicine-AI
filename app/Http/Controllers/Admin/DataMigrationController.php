<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataMigration;
use App\Models\DataMigrationRecord;
use App\Models\DataMigrationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DataMigrationController extends Controller
{
    /**
     * Display migration dashboard
     */
    public function index()
    {
        $migrations = DataMigration::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $templates = DataMigrationTemplate::with('creator')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_migrations' => DataMigration::count(),
            'completed' => DataMigration::where('status', 'completed')->count(),
            'failed' => DataMigration::where('status', 'failed')->count(),
            'in_progress' => DataMigration::where('status', 'in_progress')->count(),
        ];

        return view('admin.data-migration.index', compact('migrations', 'templates', 'stats'));
    }

    /**
     * Show create migration wizard
     */
    public function create()
    {
        $entityTypes = DataMigrationRecord::getEntityTypeOptions();
        $templates = DataMigrationTemplate::orderBy('name')->get();

        return view('admin.data-migration.create', compact('entityTypes', 'templates'));
    }

    /**
     * Store new migration
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'source_type' => 'required|in:csv,excel,api,sql_database',
            'file' => 'required_if:source_type,csv,excel|nullable|file|max:51200', // 50MB max
            'entity_type' => 'required|in:department,specialty,doctor,patient,appointment,diagnosis,prescription,treatment,allergy,insurance,user',
            'incremental_sync' => 'boolean',
            'template_id' => 'nullable|exists:data_migration_templates,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $migration = new DataMigration();
        $migration->name = $request->name;
        $migration->description = $request->description;
        $migration->source_type = $request->source_type;
        $migration->entity_type = $request->entity_type;
        $migration->incremental_sync = $request->has('incremental_sync');
        $migration->user_id = auth()->id();
        $migration->status = DataMigration::STATUS_PENDING;

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::uuid() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('data-migrations', $filename);
            $migration->source_path = $path;
        }

        // Load template if selected
        if ($request->template_id) {
            $template = DataMigrationTemplate::find($request->template_id);
            $migration->field_mapping = $template->field_mapping;
            $migration->validation_rules = $template->validation_rules;
            $migration->template_name = $template->name;
        }

        $migration->save();

        // Redirect to preview with file parsing
        return redirect()->route('admin.data-migration.preview', $migration);
    }

    /**
     * Show migration details
     */
    public function show(DataMigration $dataMigration)
    {
        $records = $dataMigration->records()
            ->orderBy('id')
            ->paginate(50);

        $failedRecords = $dataMigration->records()
            ->where('status', 'failed')
            ->get();

        return view('admin.data-migration.show', compact('dataMigration', 'records', 'failedRecords'));
    }

    /**
     * Preview uploaded file and configure field mapping
     */
    public function preview(DataMigration $dataMigration)
    {
        // Parse the uploaded file
        $filePath = Storage::path($dataMigration->source_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $handle = fopen($filePath, 'r');
            $headers = fgetcsv($handle);
            $rows = [];
            $count = 0;
            while (($row = fgetcsv($handle)) !== false && $count < 10) {
                $rows[] = $row;
                $count++;
            }
            fclose($handle);
        } else {
            // Excel file
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $headers = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
            $headers = array_map('trim', $headers);
            $rows = [];
            $count = 0;
            foreach ($sheet->rangeToArray('A2:' . $sheet->getHighestColumn() . '11') as $row) {
                if ($count >= 10) break;
                $rows[] = $row;
                $count++;
            }
        }

        // Get available fields for this entity type
        $fieldOptionsResponse = $this->getFieldOptions($dataMigration->entity_type);
        $availableFields = json_decode($fieldOptionsResponse->getContent(), true);

        // Get existing field mapping if any
        $fieldMapping = $dataMigration->field_mapping ?? [];

        return view('admin.data-migration.preview', compact('dataMigration', 'headers', 'rows', 'fieldMapping', 'availableFields'));
    }

    /**
     * Start the import process
     */
    public function start(Request $request, DataMigration $dataMigration)
    {
        if ($dataMigration->status === DataMigration::STATUS_IN_PROGRESS) {
            return redirect()->back()->with('error', 'Migration is already in progress.');
        }

        $request->validate([
            'field_mapping' => 'required|array',
            'validation_rules' => 'nullable|array',
        ]);

        $dataMigration->update([
            'status' => DataMigration::STATUS_IN_PROGRESS,
            'field_mapping' => $request->field_mapping,
            'validation_rules' => $request->validation_rules,
        ]);

        // Dispatch job
        dispatch(new \App\Jobs\ProcessDataMigration($dataMigration));

        return redirect()->route('admin.data-migration.show', $dataMigration)
            ->with('info', 'Migration started. Check progress below.');
    }

    /**
     * Cancel migration
     */
    public function cancel(DataMigration $dataMigration)
    {
        if ($dataMigration->status === DataMigration::STATUS_COMPLETED) {
            return redirect()->back()->with('error', 'Cannot cancel completed migration.');
        }

        $dataMigration->update(['status' => DataMigration::STATUS_CANCELLED]);

        return redirect()->route('admin.data-migration.show', $dataMigration)
            ->with('warning', 'Migration cancelled.');
    }

    /**
     * Delete migration
     */
    public function destroy(DataMigration $dataMigration)
    {
        // Delete associated file
        if ($dataMigration->source_path) {
            Storage::delete($dataMigration->source_path);
        }

        $dataMigration->delete();

        return redirect()->route('admin.data-migration.index')
            ->with('success', 'Migration deleted.');
    }

    /**
     * Export error report
     */
    public function exportErrors(DataMigration $dataMigration)
    {
        $failedRecords = $dataMigration->records()
            ->where('status', 'failed')
            ->get();

        $csvContent = "Source ID,Entity Type,Status,Errors,Source Data\n";
        foreach ($failedRecords as $record) {
            $errors = $record->validation_errors ? json_encode($record->validation_errors) : '';
            $sourceData = $record->source_data ? json_encode($record->source_data) : '';
            $csvContent .= "{$record->source_id},{$record->entity_type},{$record->status},\"{$errors}\",\"{$sourceData}\"\n";
        }

        $filename = "migration_errors_{$dataMigration->id}_" . date('Y-m-d') . ".csv";
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate(string $entityType)
    {
        $templates = [
            'department' => ['name', 'description', 'phone', 'email'],
            'specialty' => ['name', 'description'],
            'doctor' => ['first_name', 'last_name', 'email', 'phone', 'specialty_id', 'license_number', 'npi'],
            'patient' => ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code'],
            'appointment' => ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'duration_minutes', 'status', 'reason', 'notes'],
            'diagnosis' => ['patient_id', 'doctor_id', 'icd_code', 'description', 'diagnosis_date', 'severity', 'notes'],
            'prescription' => ['patient_id', 'doctor_id', 'medication_name', 'dosage', 'frequency', 'start_date', 'end_date', 'instructions'],
            'treatment' => ['patient_id', 'doctor_id', 'treatment_name', 'treatment_date', 'duration_minutes', 'notes', 'outcome'],
            'allergy' => ['patient_id', 'allergen', 'reaction', 'severity', 'diagnosed_date'],
            'insurance' => ['patient_id', 'provider_name', 'policy_number', 'group_number', 'subscriber_name', 'relationship', 'effective_date', 'expiration_date'],
        ];

        if (!isset($templates[$entityType])) {
            return redirect()->back()->with('error', 'Invalid entity type.');
        }

        $headers = $templates[$entityType];
        $sampleRows = [
            array_map(fn($h) => ucfirst(str_replace('_', ' ', $h)), $headers),
            array_fill(0, count($headers), 'Example'),
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleRows as $row) {
            $csvContent .= implode(',', $row) . "\n";
        }

        $filename = "import_template_{$entityType}.csv";
        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Get field options for entity type
     */
    public function getFieldOptions(string $entityType)
    {
        $fieldOptions = [
            'department' => [
                'name' => 'Department Name',
                'description' => 'Description',
                'phone' => 'Phone Number',
                'email' => 'Email Address',
                'location' => 'Location/Building',
                'floor' => 'Floor Number',
            ],
            'specialty' => [
                'name' => 'Specialty Name',
                'description' => 'Description',
                'code' => 'Specialty Code',
            ],
            'doctor' => [
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email Address',
                'phone' => 'Phone Number',
                'specialty' => 'Specialty',
                'license_number' => 'Medical License Number',
                'npi' => 'NPI Number',
                ' DEA' => 'DEA Number',
                'address' => 'Office Address',
                'city' => 'City',
                'state' => 'State',
                'zip_code' => 'ZIP Code',
            ],
            'patient' => [
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'email' => 'Email Address',
                'phone' => 'Phone Number',
                'date_of_birth' => 'Date of Birth',
                'gender' => 'Gender',
                'address' => 'Street Address',
                'city' => 'City',
                'state' => 'State',
                'zip_code' => 'ZIP Code',
                'emergency_contact_name' => 'Emergency Contact Name',
                'emergency_contact_phone' => 'Emergency Contact Phone',
                'insurance_provider' => 'Insurance Provider',
                'insurance_policy_number' => 'Insurance Policy Number',
            ],
            'appointment' => [
                'patient_id' => 'Patient ID',
                'doctor_id' => 'Doctor ID',
                'appointment_date' => 'Appointment Date',
                'appointment_time' => 'Appointment Time',
                'duration_minutes' => 'Duration (minutes)',
                'status' => 'Status',
                'reason' => 'Visit Reason',
                'notes' => 'Notes',
            ],
            'diagnosis' => [
                'patient_id' => 'Patient ID',
                'doctor_id' => 'Doctor ID',
                'icd_code' => 'ICD-10 Code',
                'description' => 'Diagnosis Description',
                'diagnosis_date' => 'Date Diagnosed',
                'severity' => 'Severity',
                'notes' => 'Clinical Notes',
            ],
            'prescription' => [
                'patient_id' => 'Patient ID',
                'doctor_id' => 'Doctor ID',
                'medication_name' => 'Medication Name',
                'dosage' => 'Dosage',
                'frequency' => 'Frequency',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'instructions' => 'Special Instructions',
                'refills' => 'Number of Refills',
            ],
            'treatment' => [
                'patient_id' => 'Patient ID',
                'doctor_id' => 'Doctor ID',
                'treatment_name' => 'Treatment Name',
                'treatment_date' => 'Treatment Date',
                'duration_minutes' => 'Duration (minutes)',
                'notes' => 'Treatment Notes',
                'outcome' => 'Outcome',
            ],
            'allergy' => [
                'patient_id' => 'Patient ID',
                'allergen' => 'Allergen Name',
                'reaction' => 'Allergic Reaction',
                'severity' => 'Severity (mild/moderate/severe)',
                'diagnosed_date' => 'Date Diagnosed',
            ],
            'insurance' => [
                'patient_id' => 'Patient ID',
                'provider_name' => 'Insurance Provider',
                'policy_number' => 'Policy Number',
                'group_number' => 'Group Number',
                'subscriber_name' => 'Subscriber Name',
                'relationship' => 'Relationship (self/spouse/child)',
                'effective_date' => 'Effective Date',
                'expiration_date' => 'Expiration Date',
                'copay' => 'Copay Amount',
            ],
        ];

        return response()->json($fieldOptions[$entityType] ?? []);
    }
}