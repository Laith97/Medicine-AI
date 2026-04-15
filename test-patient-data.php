<?php

// Test script to check patient data in database
// Run with: php artisan tinker
// Then paste this code

echo "Checking Patient Data in Database...\n";
echo "===================================\n\n";

// Check PatientAnalysis records
$patientAnalysisCount = \App\Models\PatientAnalysis::count();
echo "PatientAnalysis records: $patientAnalysisCount\n";

if ($patientAnalysisCount > 0) {
    $sampleAnalysis = \App\Models\PatientAnalysis::first();
    echo "Sample PatientAnalysis data:\n";
    echo "- ID: " . $sampleAnalysis->id . "\n";
    echo "- Name: " . $sampleAnalysis->name . "\n";
    echo "- Symptoms: " . ($sampleAnalysis->symptoms ?? 'NULL') . "\n";
    echo "- Past Medical History: " . ($sampleAnalysis->past_medical_history ?? 'NULL') . "\n";
    echo "- Allergies: " . ($sampleAnalysis->allergies ?? 'NULL') . "\n";
    echo "- Past Medications: " . ($sampleAnalysis->past_medications ?? 'NULL') . "\n";
    echo "- Patient Key: " . ($sampleAnalysis->patient_key ?? 'NULL') . "\n\n";
}

// Check PatientData records
$patientDataCount = \App\Models\PatientData::count();
echo "PatientData records: $patientDataCount\n";

if ($patientDataCount > 0) {
    $sampleData = \App\Models\PatientData::first();
    echo "Sample PatientData data:\n";
    echo "- ID: " . $sampleData->id . "\n";
    echo "- Name: " . $sampleData->name . "\n";
    echo "- Symptoms: " . ($sampleData->symptoms ?? 'NULL') . "\n";
    echo "- Past Medical History: " . ($sampleData->past_medical_history ?? 'NULL') . "\n";
    echo "- Allergies: " . ($sampleData->allergies ?? 'NULL') . "\n";
    echo "- Past Medications: " . ($sampleData->past_medications ?? 'NULL') . "\n";
    echo "- Patient Key: " . ($sampleData->patient_key ?? 'NULL') . "\n\n";
}

// Check Diagnosis records
$diagnosisCount = \App\Models\Diagnosis::count();
echo "Diagnosis records: $diagnosisCount\n";

if ($diagnosisCount > 0) {
    $sampleDiagnosis = \App\Models\Diagnosis::first();
    echo "Sample Diagnosis data:\n";
    echo "- ID: " . $sampleDiagnosis->id . "\n";
    echo "- Patient ID: " . ($sampleDiagnosis->patient_id ?? 'NULL') . "\n";
    echo "- Patient Data: " . json_encode($sampleDiagnosis->patient_data ?? []) . "\n\n";
}

echo "To test the API endpoint, try:\n";
echo "GET /api/doctor/patient-management/patient-visits/{patient_key}\n\n";

echo "Common patient keys to test:\n";
if ($patientAnalysisCount > 0) {
    $keys = \App\Models\PatientAnalysis::whereNotNull('patient_key')->pluck('patient_key')->unique()->take(3);
    foreach ($keys as $key) {
        echo "- $key\n";
    }
}

?>