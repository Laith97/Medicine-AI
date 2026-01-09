<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Clinical Documentation - {{ $documentation->patient->name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(220, 53, 69, 0.1);
            z-index: -1;
            font-weight: bold;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        .patient-info table {
            width: 100%;
        }
        .patient-info td {
            padding: 8px 5px;
            vertical-align: top;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            font-weight: bold;
            color: #007bff;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
            margin-bottom: 12px;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        .content {
            white-space: pre-wrap;
            font-size: 13px;
            text-align: justify;
            color: #444;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #777;
            text-align: center;
        }
        .code-badge {
            display: inline-block;
            background-color: #e9ecef;
            padding: 3px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            margin-right: 8px;
            font-size: 12px;
            border: 1px solid #ced4da;
        }
        .suggested-codes {
            margin-top: 10px;
        }
        .code-item {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #eee;
        }
        .code-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    @if(!$documentation->validated_by_doctor)
        <div class="watermark">DRAFT</div>
    @endif

    <div class="header">
        <h1>Clinical Documentation Intelligence</h1>
        <p style="margin-top: 5px; color: #666;">AI-Generated Medical Record</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td width="50%"><strong>Patient:</strong> {{ $documentation->patient->name }}</td>
                <td width="50%"><strong>Date:</strong> {{ $documentation->created_at->format('M d, Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Age/Gender:</strong> {{ $documentation->patient->age }}y / {{ ucfirst($documentation->patient->gender) }}</td>
                <td><strong>Note Type:</strong> {{ strtoupper($documentation->note_type) }}</td>
            </tr>
            <tr>
                <td><strong>Patient ID:</strong> #{{ $documentation->patient->id }}</td>
                <td><strong>Status:</strong> 
                    <span style="color: {{ $documentation->validated_by_doctor ? '#28a745' : '#dc3545' }}; font-weight: bold;">
                        {{ $documentation->validated_by_doctor ? 'VALIDATED' : 'PENDING APPROVAL' }}
                    </span>
                </td>
            </tr>
            @if($documentation->appointment)
            <tr>
                <td colspan="2"><strong>Appointment:</strong> {{ $documentation->appointment->appointment_date->format('F d, Y \a\t H:i') }} ({{ $documentation->appointment->appointment_type }})</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Chief Complaint</div>
        <div class="content">{{ $documentation->chief_complaint }}</div>
    </div>

    <div class="section">
        <div class="section-title">History of Present Illness</div>
        <div class="content">{{ $documentation->history_of_present_illness }}</div>
    </div>

    @if($documentation->physical_exam_findings)
    <div class="section">
        <div class="section-title">Physical Examination</div>
        <div class="content">{{ $documentation->physical_exam_findings }}</div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Assessment</div>
        <div class="content">{{ $documentation->assessment }}</div>
    </div>

    <div class="section">
        <div class="section-title">Plan & Follow-up</div>
        <div class="content">{{ $documentation->plan }}</div>
    </div>

    @if($documentation->suggestedCodes->count() > 0)
    <div class="section">
        <div class="section-title">Medical Coding Suggestions</div>
        <div class="suggested-codes">
            @foreach($documentation->suggestedCodes as $code)
                <div class="code-item">
                    <span class="code-badge">{{ $code->code_type }}: {{ $code->code_value }}</span>
                    <span style="font-size: 12px; font-weight: bold;">{{ $code->code_description }}</span>
                    @if($code->is_validated)
                        <span style="color: #28a745; font-size: 10px; margin-left: 10px;">(Verified)</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This document was generated by MedCuraAI Clinical Documentation Intelligence.</p>
        <p>Generated on {{ now()->format('F d, Y \a\t H:i:s') }} | Document ID: {{ $documentation->ai_session_id }}</p>
        @if($documentation->validated_by_doctor)
            <p style="font-weight: bold; color: #333;">Digitally Validated by Dr. {{ auth()->user()->name }} on {{ $documentation->validated_at->format('F d, Y') }}</p>
        @else
            <p style="color: #dc3545; font-weight: bold;">UNVALIDATED DRAFT - NOT FOR PERMANENT MEDICAL RECORD</p>
        @endif
    </div>
</body>
</html>
