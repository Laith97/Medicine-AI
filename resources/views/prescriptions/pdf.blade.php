<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $prescription->medication_name }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2563eb;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .prescription-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .info-section {
            flex: 1;
        }

        .info-section:not(:last-child) {
            margin-right: 20px;
        }

        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2563eb;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .info-section p {
            margin: 3px 0;
        }

        .medication-section {
            margin-bottom: 20px;
        }

        .medication-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #2563eb;
        }

        .medication-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .medication-table th,
        .medication-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
        }

        .medication-table th {
            background-color: #f8fafc;
            font-weight: bold;
            font-size: 12px;
        }

        .notes-section {
            margin-bottom: 20px;
        }

        .notes-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2563eb;
        }

        .notes-content {
            border: 1px solid #e5e7eb;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 4px;
        }

        .ai-section {
            margin-bottom: 20px;
        }

        .ai-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #dc2626;
        }

        .ai-content {
            border: 1px solid #fecaca;
            padding: 10px;
            background-color: #fef2f2;
            border-radius: 4px;
        }

        .ai-suggestions ul,
        .ai-risks ul {
            margin: 0;
            padding-left: 20px;
        }

        .ai-suggestions li,
        .ai-risks li {
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }

        .signature-section {
            margin-top: 40px;
            text-align: right;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            display: inline-block;
            margin-bottom: 5px;
        }

        @media print {
            body {
                padding: 15px;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Medical Prescription</h1>
        <p>Prescription ID: {{ $prescription->id }}</p>
        <p>Date Issued: {{ $prescription->created_at->format('F j, Y') }}</p>
    </div>

    <div class="prescription-info">
        <div class="info-section">
            <h3>Doctor Information</h3>
            <p><strong>Name:</strong> {{ $prescription->doctor->name }}</p>
            <p><strong>Email:</strong> {{ $prescription->doctor->email }}</p>
            @if($prescription->doctor->phone)
                <p><strong>Phone:</strong> {{ $prescription->doctor->phone }}</p>
            @endif
        </div>

        <div class="info-section">
            <h3>Patient Information</h3>
            <p><strong>Name:</strong> {{ $prescription->patient->name }}</p>
            <p><strong>Email:</strong> {{ $prescription->patient->email }}</p>
            @if($prescription->patient->phone)
                <p><strong>Phone:</strong> {{ $prescription->patient->phone }}</p>
            @endif
            @if($prescription->patient->date_of_birth)
                <p><strong>Date of Birth:</strong> {{ $prescription->patient->date_of_birth->format('F j, Y') }}</p>
            @endif
            @if($prescription->patient->age)
                <p><strong>Age:</strong> {{ $prescription->patient->age }} years</p>
            @endif
        </div>

        <div class="info-section">
            <h3>Appointment Details</h3>
            <p><strong>Date:</strong> {{ $prescription->appointment->appointment_date->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Type:</strong> {{ $prescription->appointment->appointment_type ?? 'Regular Consultation' }}</p>
            @if($prescription->appointment->reason)
                <p><strong>Reason:</strong> {{ $prescription->appointment->reason }}</p>
            @endif
        </div>
    </div>

    <div class="medication-section">
        <h3>Current Prescription</h3>
        <table class="medication-table">
            <thead>
                <tr>
                    <th>Medication Name</th>
                    <th>Dosage</th>
                    <th>Form</th>
                    <th>Route</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Quantity</th>
                    <th>Refills</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $prescription->medication_name }}</td>
                    <td>{{ $prescription->dosage }}</td>
                    <td>{{ ucfirst($prescription->form ?? 'N/A') }}</td>
                    <td>{{ ucfirst($prescription->route ?? 'N/A') }}</td>
                    <td>{{ $prescription->frequency }}</td>
                    <td>{{ $prescription->duration }}</td>
                    <td>{{ $prescription->quantity ?? 'N/A' }}</td>
                    <td>{{ $prescription->refills ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($prescription->indication || $prescription->start_date || $prescription->instructions)
    <div class="medication-section">
        <h3>Prescription Details</h3>
        <table class="medication-table">
            <tbody>
                @if($prescription->indication)
                <tr>
                    <td style="font-weight: bold; width: 30%;">Indication:</td>
                    <td>{{ $prescription->indication }}</td>
                </tr>
                @endif
                @if($prescription->start_date)
                <tr>
                    <td style="font-weight: bold; width: 30%;">Start Date:</td>
                    <td>{{ $prescription->start_date->format('F j, Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: bold; width: 30%;">Generic Allowed:</td>
                    <td>{{ $prescription->generic_allowed ? 'Yes' : 'No' }}</td>
                </tr>
                @if($prescription->instructions)
                <tr>
                    <td style="font-weight: bold; width: 30%;">Patient Instructions:</td>
                    <td>{{ $prescription->instructions }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    @if($activePrescriptions && $activePrescriptions->count() > 0)
    <div class="medication-section">
        <h3>Active Medications</h3>
        <table class="medication-table">
            <thead>
                <tr>
                    <th>Medication Name</th>
                    <th>Dosage</th>
                    <th>Form</th>
                    <th>Route</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Quantity</th>
                    <th>Refills</th>
                    <th>Prescribed Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($activePrescriptions as $activePrescription)
                <tr>
                    <td>{{ $activePrescription->medication_name }}</td>
                    <td>{{ $activePrescription->dosage }}</td>
                    <td>{{ ucfirst($activePrescription->form ?? 'N/A') }}</td>
                    <td>{{ ucfirst($activePrescription->route ?? 'N/A') }}</td>
                    <td>{{ $activePrescription->frequency }}</td>
                    <td>{{ $activePrescription->duration }}</td>
                    <td>{{ $activePrescription->quantity ?? 'N/A' }}</td>
                    <td>{{ $activePrescription->refills ?? 0 }}</td>
                    <td>{{ $activePrescription->created_at->format('F j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($medicationHistory && count($medicationHistory) > 0)
    <div class="medication-section">
        <h3>Medication History</h3>
        <div class="notes-content">
            <strong>Previously Used Medications:</strong>
            <ul>
                @foreach($medicationHistory as $med)
                    <li>{{ $med }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if($prescription->notes)
    <div class="notes-section">
        <h3>Additional Notes</h3>
        <div class="notes-content">
            {{ $prescription->notes }}
        </div>
    </div>
    @endif

    @if($prescription->ai_suggestions && count($prescription->ai_suggestions) > 0)
    <div class="ai-section">
        <h3>AI-Generated Suggestions</h3>
        <div class="ai-content">
            <div class="ai-suggestions">
                <strong>Recommended Alternatives/Considerations:</strong>
                <ul>
                    @foreach($prescription->ai_suggestions as $suggestion)
                        <li>{{ $suggestion['med'] ?? $suggestion }} - {{ $suggestion['dosage'] ?? '' }} {{ $suggestion['freq'] ?? '' }} for {{ $suggestion['dur'] ?? '' }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if($prescription->ai_risk_flags && count($prescription->ai_risk_flags) > 0)
    <div class="ai-section">
        <h3>AI-Identified Risk Flags</h3>
        <div class="ai-content">
            <div class="ai-risks">
                <strong>Important Warnings:</strong>
                <ul>
                    @foreach($prescription->ai_risk_flags as $risk)
                        <li>{{ $risk }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-line"></div>
        <p><strong>Doctor's Signature</strong></p>
        <p>{{ $prescription->doctor->name }}</p>
        <p>Date: {{ $prescription->created_at->format('F j, Y') }}</p>
    </div>

    <div class="footer">
        <p>This prescription was generated electronically and is valid for dispensing according to local regulations.</p>
        <p>Please consult your healthcare provider before making any changes to your medication regimen.</p>
    </div>
</body>
</html>