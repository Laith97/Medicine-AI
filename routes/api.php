<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationTestController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\EligibilityController;
use App\Http\Controllers\UserSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth')->post('/predictions', [App\Http\Controllers\Api\PredictionController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'web'])->group(function () {
    // User settings
    Route::get('/user/settings', [UserSettingsController::class, 'getSettings']);

    // Get notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Mark as read
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Delete notifications
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Notification testing and diagnosis routes
    Route::post('/test-notification', [NotificationTestController::class, 'sendTestNotification']);
    Route::post('/test-appointment-notification', [NotificationTestController::class, 'sendTestAppointmentNotification']);
    Route::post('/test/notification', [NotificationTestController::class, 'sendEnhancedTestNotification']);
    Route::get('/notification-preferences', [NotificationTestController::class, 'getNotificationPreferences']);
    Route::get('/queue-status', [NotificationTestController::class, 'getQueueStatus']);
    Route::get('/pusher-config', [NotificationTestController::class, 'testPusherConfig']);

    // Direct notification testing (bypasses queue) - import controller
    Route::post('/test/direct-notification', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'sendDirectTest']);
    Route::post('/test/pusher-connection', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'testPusherConnection']);
    Route::get('/test/system-status', [\App\Http\Controllers\Api\DirectNotificationTestController::class, 'getSystemStatus']);

    // Billing API
    Route::middleware('billing.rate:suggestions')->post('/billing/suggest_codes', [BillingController::class, 'suggestCodes']);
    Route::middleware('billing.rate:prediction')->post('/billing/predict_denial', [BillingController::class, 'predictDenial']);
    Route::middleware('billing.rate:analysis')->get('/billing/underpayments/{claimId}', [BillingController::class, 'getUnderpayments']);

    // AI-powered claims features
    Route::middleware('billing.rate:suggestions')->post('/ai/code-suggestions', [BillingController::class, 'getCodeSuggestions']);
    Route::middleware('billing.rate:prediction')->post('/ai/denial-prediction', [BillingController::class, 'getDenialPrediction']);

    // Payer rules checking
    Route::post('/hospital-admin/claims/check-rules', [App\Http\Controllers\HospitalAdmin\ClaimController::class, 'checkRules']);

    // Eligibility verification API routes
    Route::middleware(['eligibility.access:eligibility.check', 'eligibility.rate:check'])->post('/eligibility/check', [EligibilityController::class, 'check']);
    Route::middleware(['eligibility.access:eligibility.view', 'eligibility.rate:status'])->get('/eligibility/{patientId}/status', [EligibilityController::class, 'getStatus']);
    Route::middleware(['eligibility.access:eligibility.batch', 'eligibility.rate:batch'])->post('/eligibility/batch-check', [EligibilityController::class, 'batchCheck']);
    Route::middleware('eligibility.access:eligibility.view')->get('/eligibility/batch/{batchId}/results', [EligibilityController::class, 'getBatchResults']);

    // Patient Insurance API routes
    Route::middleware('eligibility.access:eligibility.view')->get('/patient-insurance', [App\Http\Controllers\Api\PatientInsuranceController::class, 'index']);
    Route::middleware('eligibility.access:eligibility.manage')->post('/patient-insurance', [App\Http\Controllers\Api\PatientInsuranceController::class, 'store']);
    Route::middleware('eligibility.access:eligibility.view')->get('/patient-insurance/{insurance}', [App\Http\Controllers\Api\PatientInsuranceController::class, 'show']);
    Route::middleware('eligibility.access:eligibility.manage')->put('/patient-insurance/{insurance}', [App\Http\Controllers\Api\PatientInsuranceController::class, 'update']);
    Route::middleware('eligibility.access:eligibility.manage')->delete('/patient-insurance/{insurance}', [App\Http\Controllers\Api\PatientInsuranceController::class, 'destroy']);

    // Patient cases and visit history API routes
    Route::get('/doctor/patient-management/patient-visits/{patientKey}', [\App\Http\Controllers\OpenAIController::class, 'getPatientVisits']);
    Route::get('/doctor/patient-management/visit-history/{id}', [\App\Http\Controllers\OpenAIController::class, 'getVisitDetails']);

    /*
    |--------------------------------------------------------------------------
    | Kiosk API Routes
    |--------------------------------------------------------------------------
    */

    // Kiosk management routes (admin/hospital_admin only)
    Route::middleware('role:admin,hospital_admin')->group(function () {
        Route::post('/kiosks/register', [\App\Http\Controllers\Api\KioskController::class, 'register']);
        Route::post('/kiosks/{kiosk}/ping', [\App\Http\Controllers\Api\KioskController::class, 'ping']);
        Route::get('/kiosks/{kiosk}/status', [\App\Http\Controllers\Api\KioskController::class, 'status']);
        Route::put('/kiosks/{kiosk}/configuration', [\App\Http\Controllers\Api\KioskController::class, 'updateConfiguration']);

        // Remote management and updates
        Route::post('/kiosks/{kiosk}/command', [\App\Http\Controllers\Api\KioskController::class, 'sendCommand']);
        Route::get('/kiosks/{kiosk}/commands/pending', [\App\Http\Controllers\Api\KioskController::class, 'getPendingCommands']);
        Route::post('/kiosks/{kiosk}/commands/acknowledge', [\App\Http\Controllers\Api\KioskController::class, 'acknowledgeCommand']);
        Route::get('/kiosks/{kiosk}/software/update', [\App\Http\Controllers\Api\KioskController::class, 'getSoftwareUpdate']);
        Route::get('/kiosks/{kiosk}/software/download', [\App\Http\Controllers\Api\KioskController::class, 'downloadSoftwareUpdate']);
        Route::post('/kiosks/{kiosk}/software/status', [\App\Http\Controllers\Api\KioskController::class, 'reportUpdateStatus']);
    });

    // Kiosk session routes (accessible by kiosks)
    Route::post('/kiosk-sessions/start/{kiosk}', [\App\Http\Controllers\Api\KioskController::class, 'startSession']);
    Route::post('/kiosk-sessions/{session}/end', [\App\Http\Controllers\Api\KioskController::class, 'endSession']);

    // Check-in routes (accessible by kiosks)
    Route::get('/appointments/search', [\App\Http\Controllers\Api\KioskCheckinController::class, 'searchAppointments']);
    Route::get('/appointments/{appointment}/details', [\App\Http\Controllers\Api\KioskCheckinController::class, 'getAppointment']);
    Route::post('/appointments/{appointment}/checkin', [\App\Http\Controllers\Api\KioskCheckinController::class, 'checkin']);
    Route::get('/kiosk-sessions/{session}/checkins', [\App\Http\Controllers\Api\KioskCheckinController::class, 'getSessionCheckins']);

    // Payment routes (accessible by kiosks)
    Route::post('/appointments/{appointment}/payments/create-intent', [\App\Http\Controllers\Api\KioskPaymentController::class, 'createPaymentIntent']);
    Route::post('/payments/{payment}/confirm', [\App\Http\Controllers\Api\KioskPaymentController::class, 'confirmPayment']);
    Route::get('/payments/{payment}/status', [\App\Http\Controllers\Api\KioskPaymentController::class, 'getPaymentStatus']);
    Route::post('/payments/{payment}/refund', [\App\Http\Controllers\Api\KioskPaymentController::class, 'refundPayment']);
    Route::get('/kiosk-sessions/{session}/payments', [\App\Http\Controllers\Api\KioskPaymentController::class, 'getSessionPayments']);
    /*
    |--------------------------------------------------------------------------
    | HEP (Home Exercise Program) API Routes
    |--------------------------------------------------------------------------
    */

    // HEP Program Management
    Route::middleware('hep.rate:generate')->post('/hep/generate', [App\Http\Controllers\Api\HEPController::class, 'generate']);
    Route::middleware('hep.rate:default')->get('/hep/programs', [App\Http\Controllers\Api\HEPController::class, 'index']);
    Route::middleware('hep.rate:default')->get('/hep/programs/{program}', [App\Http\Controllers\Api\HEPController::class, 'show']);
    Route::middleware('hep.rate:default')->put('/hep/programs/{program}', [App\Http\Controllers\Api\HEPController::class, 'update']);
    Route::middleware('hep.rate:default')->delete('/hep/programs/{program}', [App\Http\Controllers\Api\HEPController::class, 'destroy']);
    Route::middleware('hep.rate:default')->post('/hep/programs/{program}/compliance-document', [App\Http\Controllers\Api\HEPController::class, 'generateComplianceDocument']);

    // HEP Assignments
    Route::middleware('hep.rate:assignment')->post('/hep/assignments', [App\Http\Controllers\Api\HEPController::class, 'createAssignment']);
    Route::middleware('hep.rate:default')->get('/hep/assignments', [App\Http\Controllers\Api\HEPController::class, 'getAssignments']);
    Route::middleware('hep.rate:assignment')->put('/hep/assignments/{assignment}', [App\Http\Controllers\Api\HEPController::class, 'updateAssignment']);

    // HEP Progress Tracking
    Route::middleware('hep.rate:progress')->post('/hep/assignments/{assignment}/progress', [App\Http\Controllers\Api\HEPController::class, 'updateProgress']);
    Route::middleware('hep.rate:default')->get('/hep/assignments/{assignment}/progress', [App\Http\Controllers\Api\HEPController::class, 'getProgress']);

    // Exercise Library
    Route::middleware('hep.rate:default')->get('/hep/exercises', [App\Http\Controllers\Api\HEPController::class, 'getExercises']);

    /*
    |--------------------------------------------------------------------------
    | HEP Analytics & Reporting API Routes
    |--------------------------------------------------------------------------
    */

    // Analytics endpoints (admin, hospital_admin, doctor roles)
    Route::middleware('role:admin,hospital_admin,doctor')->group(function () {
        Route::get('/hep/analytics/clinical-effectiveness', [App\Http\Controllers\HEPAnalyticsController::class, 'getClinicalEffectiveness']);
        Route::get('/hep/analytics/adherence-patterns', [App\Http\Controllers\HEPAnalyticsController::class, 'getAdherencePatterns']);
        Route::get('/hep/analytics/clinician-metrics', [App\Http\Controllers\HEPAnalyticsController::class, 'getClinicianMetrics']);
        Route::get('/hep/analytics/dashboard', [App\Http\Controllers\HEPAnalyticsController::class, 'getDashboardData']);
        Route::post('/hep/analytics/clear-cache', [App\Http\Controllers\HEPAnalyticsController::class, 'clearCache']);
    });

    // Export endpoints (admin, hospital_admin only for research, doctors for patient data)
    Route::middleware('role:admin,hospital_admin')->post('/hep/export/research', [App\Http\Controllers\HEPAnalyticsController::class, 'exportForResearch']);
    Route::middleware('role:admin,hospital_admin,doctor')->post('/hep/export/insurance/{patientId}', [App\Http\Controllers\HEPAnalyticsController::class, 'exportForInsurance']);
    Route::get('/hep/export/formats', [App\Http\Controllers\HEPAnalyticsController::class, 'getExportFormats']);

    /*
    |--------------------------------------------------------------------------
    | Waitlist Preference API Routes
    |--------------------------------------------------------------------------
    */

    // Waitlist preference management
    Route::get('/waitlist/preferences', [App\Http\Controllers\Api\WaitlistPreferenceController::class, 'index']);
    Route::post('/waitlist/preferences', [App\Http\Controllers\Api\WaitlistPreferenceController::class, 'store']);
    Route::put('/waitlist/preferences/{id}', [App\Http\Controllers\Api\WaitlistPreferenceController::class, 'update']);

    // Smart matching and recommendations
    Route::get('/waitlist/doctors/{doctorId}/recommendations', [App\Http\Controllers\Api\WaitlistPreferenceController::class, 'getMatchingRecommendations']);

    // Preference analytics
    Route::get('/waitlist/preferences/analytics', [App\Http\Controllers\Api\WaitlistPreferenceController::class, 'getAnalytics']);

    /*
    |--------------------------------------------------------------------------
    | Advanced Analytics API Routes
    |--------------------------------------------------------------------------
    */

    // Analytics routes with custom analytics access middleware
    Route::middleware('analytics.access')->prefix('analytics')->group(function () {
        // Dashboard endpoints
        Route::get('/dashboard/executive', [App\Http\Controllers\Api\AnalyticsController::class, 'getExecutiveDashboard'])
            ->middleware('analytics.access:dashboard.executive.read');
        Route::get('/revenue/overview', [App\Http\Controllers\Api\AnalyticsController::class, 'getRevenueAnalytics'])
            ->middleware('analytics.access:dashboard.revenue.read');
        Route::get('/patients/satisfaction', [App\Http\Controllers\Api\AnalyticsController::class, 'getPatientSatisfaction'])
            ->middleware('analytics.access:kpi.patient_satisfaction.view');

        // Export endpoints
        Route::post('/export/dashboard', [App\Http\Controllers\Api\AnalyticsController::class, 'exportDashboard'])
            ->middleware('analytics.access:feature.export_data');

        // User permissions endpoint
        Route::get('/permissions', [App\Http\Controllers\Api\AnalyticsController::class, 'getUserPermissions']);

        /*
        |--------------------------------------------------------------------------
        | Real-time Appointment API Routes
        |--------------------------------------------------------------------------
        */

        // Real-time appointment endpoints
        Route::get('/appointments/today', [App\Http\Controllers\Api\AppointmentRealtimeController::class, 'getTodaysAppointments']);
        Route::post('/appointments/subscribe', [App\Http\Controllers\Api\AppointmentRealtimeController::class, 'subscribeToUpdates']);
        Route::post('/appointments/unsubscribe', [App\Http\Controllers\Api\AppointmentRealtimeController::class, 'unsubscribeFromUpdates']);

        /*
        |--------------------------------------------------------------------------
        | Document Workflow Automation API Routes
        |--------------------------------------------------------------------------
        */

        // Document creation and management
        Route::post('/documents/create', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'createDocument']);
        Route::post('/documents/{document}/submit', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'submitDocument']);
        Route::get('/documents/{document}/status', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'getWorkflowStatus']);
        Route::get('/documents/{document}/compliance', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'getComplianceStatus']);

        // Review and approval
        Route::post('/tasks/{task}/review', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'processReview']);
        Route::get('/tasks/assigned', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'getAssignedTasks']);

        // Templates
        Route::get('/templates', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'getTemplates']);
        Route::post('/templates/{template}/preview', [App\Http\Controllers\Api\DocumentWorkflowController::class, 'previewTemplate']);

        /*
        |--------------------------------------------------------------------------
        | Document Acceleration API Routes (Phase 4)
        |--------------------------------------------------------------------------
        */

        // AI Writing Assistant
        Route::post('/documents/generate-ai', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'generateWithAI']);
        Route::post('/documents/{document}/enhance-ai', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'enhanceWithAI']);
        Route::post('/documents/generate-patient-section', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'generatePatientSection']);

        // Template Auto-fill
        Route::post('/templates/{template}/autofill', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'autofillTemplate']);
        Route::get('/templates/{template}/autofill-suggestions', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'getAutofillSuggestions']);
        Route::post('/documents/create-smart', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'createSmartDocument']);

        // Compliance Document Checker
        Route::post('/documents/{document}/compliance-check', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'checkCompliance']);
        Route::get('/documents/{document}/compliance-history', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'getComplianceHistory']);

        // Version Control
        Route::post('/documents/{document}/versions', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'createVersion']);
        Route::get('/documents/{document}/versions', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'getVersionHistory']);
        Route::post('/documents/{document}/versions/{version}/restore', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'restoreVersion']);
        Route::get('/documents/{document}/versions/compare', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'compareVersions']);
        Route::get('/documents/{document}/audit-trail', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'getAuditTrail']);
        Route::post('/documents/{document}/versions/archive', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'archiveVersions']);
        Route::get('/documents/{document}/versions/export', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'exportVersionHistory']);
        Route::get('/documents/{document}/version-stats', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'getVersionStatistics']);
        Route::post('/documents/{document}/versions/{version}/validate', [App\Http\Controllers\Api\DocumentAccelerationController::class, 'validateVersionIntegrity']);

        /*
        |--------------------------------------------------------------------------
        | Compliance Integration API Routes (Phase 5)
        |--------------------------------------------------------------------------
        */

        // Compliance audit trail and export
        Route::post('/compliance/export-audit-trail', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'exportAuditTrail']);
        Route::post('/compliance/audit-report', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'generateAuditReport']);
        Route::get('/compliance/analytics', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'getComplianceAnalytics']);

        // SOAP integration
        Route::post('/compliance/soap/send', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'sendSOAPData']);
        Route::post('/compliance/soap/register-client', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'registerSOAPClient']);
        Route::get('/compliance/soap/clients', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'getSOAPClients']);

        // Webhook management
        Route::post('/compliance/webhooks/register', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'registerWebhook']);
        Route::post('/compliance/webhooks/test', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'testWebhook']);
        Route::get('/compliance/webhooks', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'getWebhooks']);
        });

    // Clinical Monitoring Routes
    Route::prefix('monitoring')->group(function () {
        Route::post('{patient_id}/vitals', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'receiveVitals']);
        Route::post('{patient_id}/labs', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'receiveLabs']);
        Route::post('{patient_id}/notes', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'receiveNotes']);
        
        Route::get('alerts', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'getAlerts']);
        Route::post('alerts/{id}/acknowledge', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'acknowledgeAlert']);
        Route::post('alerts/{id}/escalate', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'escalateAlert']);
        
        Route::get('rules', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'getRules']);
        Route::put('rules/{id}', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'updateRule']);

        Route::get('patients/{patient_id}/scores', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'getHistoricalScores']);
        Route::get('patients/{patient_id}/insights', [App\Http\Controllers\Api\ClinicalMonitoringController::class, 'getLatestInsights']);
    });

    // Treatment Optimization Routes
    Route::prefix('treatment-optimization')->group(function () {
        Route::get('{patient_id}/{appointment_id}', [App\Http\Controllers\Api\TreatmentOptimizationController::class, 'index']);
        Route::post('generate', [App\Http\Controllers\Api\TreatmentOptimizationController::class, 'store']);
        Route::post('{id}/validate', [App\Http\Controllers\Api\TreatmentOptimizationController::class, 'validateRecommendation']);
        Route::post('{id}/reject', [App\Http\Controllers\Api\TreatmentOptimizationController::class, 'rejectRecommendation']);
    });
});

// Public routes (for guest access with token verification)
Route::get('/notifications/guest/{token}', [NotificationController::class, 'guestNotifications']);

// Compliance Integration Webhook (public endpoint for external systems)
Route::post('/compliance/webhooks/incoming', [App\Http\Controllers\Api\ComplianceIntegrationController::class, 'processIncomingWebhook']);
