<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Prescription;

$appointment = Appointment::find(1);

if (!$appointment) {
    echo "Appointment with ID 1 not found.\n";
    exit;
}

echo "Appointment ID: " . $appointment->id . "\n";
echo "Patient ID: " . $appointment->patient_id . "\n";
echo "Doctor ID: " . $appointment->doctor_id . "\n";
echo "Date: " . $appointment->appointment_date . "\n";
echo "Status: " . $appointment->status . "\n";
echo "Notes: " . $appointment->notes . "\n";
echo "Prescription Given: " . ($appointment->prescription_given ? 'Yes' : 'No') . "\n";

$prescriptions = $appointment->prescriptions;

echo "\nPrescriptions:\n";
if ($prescriptions->isEmpty()) {
    echo "No prescriptions found for this appointment.\n";
} else {
    foreach ($prescriptions as $prescription) {
        echo "Prescription ID: " . $prescription->id . "\n";
        echo "Medication Name: " . $prescription->medication_name . "\n";
        echo "Dosage: " . $prescription->dosage . "\n";
        echo "Frequency: " . $prescription->frequency . "\n";
        echo "Duration: " . $prescription->duration . "\n";
        echo "Notes: " . $prescription->notes . "\n";
        echo "AI Suggestions: " . json_encode($prescription->ai_suggestions) . "\n";
        echo "AI Risk Flags: " . json_encode($prescription->ai_risk_flags) . "\n";
        echo "---\n";
    }
}

echo "\nTotal prescriptions in database: " . Prescription::count() . "\n";
echo "Prescriptions for other appointments exist: " . (Prescription::where('appointment_id', '!=', 1)->exists() ? 'Yes' : 'No') . "\n";