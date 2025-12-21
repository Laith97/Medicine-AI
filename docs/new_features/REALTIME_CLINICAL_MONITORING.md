# New Feature Proposal: Real-time Clinical Monitoring & Alerting System

This document outlines a proposal for a powerful new feature for MedcuraAI: a **Real-time Clinical Monitoring & Alerting System**.

## 1. Feature Overview

This system will enable healthcare providers to monitor patient vital signs and other critical data points in near real-time. It will automatically trigger alerts when data exceeds predefined thresholds, allowing for timely intervention and improved patient outcomes, particularly for chronic disease management and post-operative care.

### Key Capabilities
- **Real-time Data Streams:** Ingest and process streams of patient data (e.g., from wearables, home monitoring devices, or manual patient input).
- **Configurable Alerting Rules:** Allow clinicians to set patient-specific or general rules for alerts (e.g., Blood Pressure > 140/90, SpO2 < 92%).
- **Automated Alerts:** Integrate with the existing notification system to send immediate alerts to the care team via in-app, SMS, and email.
- **Clinician Dashboard:** A dedicated dashboard to visualize real-time patient data, manage active alerts, and view historical trends.
- **Patient-Reported Outcomes (PROs):** A mechanism for patients to manually submit their vitals or symptoms through the patient portal.

## 2. High-Level Architecture

This feature will be built on MedcuraAI's existing Laravel, Echo, and real-time infrastructure.

1.  **Data Ingestion Endpoint:** A new API endpoint to receive patient data.
2.  **Data Processing Job:** A queued job to process incoming data, check against rules, and store it.
3.  **Rule Engine Service:** A service to manage and evaluate alerting rules.
4.  **Clinical Alert Notification:** A new notification type for clinical alerts.
5.  **Real-time Dashboard:** A new React component for the clinician dashboard.

## 3. Proposed New Components

### Database
- `database/migrations/YYYY_MM_DD_HHMMSS_create_patient_vitals_table.php`: To store time-series vital sign data.
  - `patient_id`, `vital_type` (e.g., 'blood_pressure', 'spo2'), `value`, `timestamp`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_clinical_alert_rules_table.php`: To store patient-specific alert rules.
  - `patient_id`, `vital_type`, `condition` (e.g., '>', '<'), `threshold`, `is_active`
- `database/migrations/YYYY_MM_DD_HHMMSS_add_clinical_alert_notification_type.php`: Add a new type to `notification_types`.

### Backend (app/)
- `app/Models/PatientVital.php`
- `app/Models/ClinicalAlertRule.php`
- `app/Services/RealtimeMonitoringService.php`: The core service to handle data processing and rule evaluation.
- `app/Jobs/ProcessPatientVitalJob.php`: Queued job to handle incoming data.
- `app/Http/Controllers/Api/PatientVitalsController.php`: API endpoint for data ingestion.
- `app/Http/Controllers/Doctor/MonitoringDashboardController.php`: Controller for the new dashboard.
- `app/Notifications/ClinicalAlertNotification.php`: New notification class.

### Frontend (resources/)
- `resources/views/doctor/monitoring/dashboard.blade.php`: The main view for the dashboard.
- `resources/js/components/RealtimeVitalsChart.jsx`: A component to display live data graphs.
- `resources/js/pages/clinical-monitoring.js`: JavaScript for the dashboard page.

## 4. Sample Usage Scenario

1.  **Configuration:** Dr. Smith sets up a monitoring rule for his patient, John Doe, who is recovering from cardiac surgery. The rule is: `Alert if Heart Rate > 100 bpm for more than 5 minutes`.
2.  **Data Submission:** John Doe uses his home monitoring device, which sends his heart rate data to MedcuraAI's API every minute.
3.  **Processing:** `ProcessPatientVitalJob` processes each incoming data point. The `RealtimeMonitoringService` checks the data against Mr. Doe's alert rules.
4.  **Alert Triggered:** John's heart rate stays at 105 bpm for 5 consecutive minutes. The system triggers a `ClinicalAlertNotification`.
5.  **Notification:** Dr. Smith receives an immediate high-priority notification on his phone and in the MedcuraAI web app.
6.  **Action:** Dr. Smith clicks the notification, which takes him to the **Real-time Clinical Monitoring Dashboard**. He sees a live graph of John's heart rate, confirms the tachycardia, and initiates a video call with the patient through the EMR.

## 5. Next Steps

If this proposal is approved, the implementation can begin with the following steps:
1.  Create the necessary database migrations and models.
2.  Develop the backend services for data processing and rule evaluation.
3.  Build the API endpoint for data ingestion.
4.  Implement the clinician dashboard and UI components.
5.  Integrate with the notification system.

This feature will significantly enhance the proactive care capabilities of MedcuraAI and provide immense value to clinicians and patients.
