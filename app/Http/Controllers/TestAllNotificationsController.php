<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\WorkflowTask;
use App\Models\HepAssignment;
use App\Models\HepProgram;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\StripeInvoice;
use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Models\MonthlyInvoiceSetting;
use App\Models\WaitlistEntry;
use App\Models\Diagnosis;
use App\Models\Review;
use App\Models\VoiceTranscription;
use App\Notifications\TestNotification;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Notifications\WaitlistAutoBookedNotification;
use App\Notifications\WaitlistSlotAvailableNotification;
use App\Notifications\WaitlistPositionUpdateNotification;
use App\Notifications\WaitlistOfferExpiringNotification;
use App\Notifications\WaitlistExpiredNotification;
use App\Notifications\HighRiskClaimAlert;
use App\Notifications\UnderpaymentAlert;
use App\Notifications\UrgentTaskAlert;
use App\Notifications\HEPSafetyAlert;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use App\Notifications\InvoiceCreated;
use App\Notifications\InvoiceDueSoon;
use App\Notifications\InvoiceOverdue;
use App\Notifications\InvoiceReminder;
use App\Notifications\MonthlyInvoiceCreated;
use App\Notifications\HEPProgramGenerated;
use App\Notifications\HEPExerciseReminder;
use App\Notifications\EligibilityCheckFailedNotification;
use App\Notifications\EligibilityExpiringNotification;
use App\Notifications\SystemAlertNotification;
use App\Notifications\KioskOffline;
use App\Notifications\KioskSessionTimeout;
use App\Notifications\AccountRestricted;
use App\Notifications\FinalWarning;
use App\Notifications\GracePeriodReminder;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VoiceTranscriptionCompletedNotification;
use App\Notifications\VoiceAssistantPerformanceAlert;
use App\Notifications\DiagnosisSubmittedNotification;
use App\Notifications\ReviewSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use App\Events\AppointmentBookedEvent;

class TestAllNotificationsController extends Controller
{
    /**
     * Display test page with all notification buttons
     */
    public function index()
    {
        return view('test-all-notifications');
    }

    /**
     * Send a specific notification type
     */
    public function send(Request $request)
    {
        $user = Auth::user();
        $type = $request->input('type');

        Log::info('Test notification requested', ['type' => $type, 'user_id' => $user->id]);

        try {
            $notification = match($type) {
                // Appointments
                'appointment_booked' => $this->createAppointmentBookedNotification($user),
                'appointment_status_changed' => $this->createAppointmentStatusChangedNotification($user),

                // Waitlist
                'waitlist_auto_booked' => $this->createWaitlistAutoBookedNotification($user),
                'waitlist_slot_available' => $this->createWaitlistSlotAvailableNotification($user),
                'waitlist_position_update' => $this->createWaitlistPositionUpdateNotification($user),
                'waitlist_offer_expiring' => $this->createWaitlistOfferExpiringNotification($user),
                'waitlist_expired' => $this->createWaitlistExpiredNotification($user),

                // Claims & Alerts
                'high_risk_claim' => $this->createHighRiskClaimAlert($user),
                'underpayment_alert' => $this->createUnderpaymentAlert($user),
                'urgent_task' => $this->createUrgentTaskNotification($user),
                'hep_safety_alert' => $this->createHEPSafetyAlertNotification($user),

                // Tasks
                'task_overdue' => $this->createTaskOverdueNotification($user),
                'task_reminder' => $this->createTaskReminderNotification($user),

                // Invoices
                'invoice_created' => $this->createInvoiceCreatedNotification($user),
                'invoice_due_soon' => $this->createInvoiceDueSoonNotification($user),
                'invoice_overdue' => $this->createInvoiceOverdueNotification($user),
                'invoice_reminder' => $this->createInvoiceReminderNotification($user),
                'monthly_invoice' => $this->createMonthlyInvoiceNotification($user),

                // HEP
                'hep_program_generated' => $this->createHEPProgramGeneratedNotification($user),
                'hep_exercise_reminder' => $this->createHEPExerciseReminderNotification($user),

                // Eligibility
                'eligibility_check_failed' => $this->createEligibilityCheckFailedNotification($user),
                'eligibility_expiring' => $this->createEligibilityExpiringNotification($user),

                // System
                'system_alert' => $this->createSystemAlertNotification(),
                'kiosk_offline' => $this->createKioskOfflineNotification(),
                'kiosk_session_timeout' => $this->createKioskSessionTimeoutNotification(),

                // Account
                'account_restricted' => $this->createAccountRestrictedNotification($user),
                'final_warning' => $this->createFinalWarningNotification($user),
                'grace_period' => $this->createGracePeriodReminderNotification($user),
                'password_reset' => $this->createPasswordResetNotification(),

                // Voice
                'voice_transcription' => $this->createVoiceTranscriptionNotification(),
                'voice_performance_alert' => $this->createVoicePerformanceAlertNotification(),

                // Other
                'diagnosis_submitted' => $this->createDiagnosisSubmittedNotification($user),
                'review_submitted' => $this->createReviewSubmittedNotification($user),

                // Default test notification
                default => $this->createTestNotification(),
            };

            // Disable observers temporarily to prevent double broadcasting
            Event::listen(AppointmentBookedEvent::class, function () {
                // Suppress AppointmentBookedEvent during test
            });

            $user->notify($notification);

            Log::info('Test notification sent successfully', ['type' => $type, 'user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent: ' . $type,
                'type' => $type,
                'notification_class' => get_class($notification),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function createAppointmentBookedNotification($user)
    {
        // Create appointment WITHOUT triggering observer by using withoutEvents
        $appointment = Appointment::withoutEvents(function () {
            $doctor = Doctor::first();
            if (!$doctor) {
                $doctor = Doctor::factory()->create();
            }
            $patientUser = User::first();
            if (!$patientUser) {
                $patientUser = User::factory()->create();
            }

            return Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patientUser->id,
                'guest_name' => 'Test Patient',
                'appointment_date' => Carbon::now()->addDays(3),
                'status' => 'pending',
            ]);
        });

        return new AppointmentBookedNotification($appointment);
    }

    private function createAppointmentStatusChangedNotification($user)
    {
        $appointment = Appointment::withoutEvents(function () {
            $doctor = Doctor::first();
            if (!$doctor) {
                $doctor = Doctor::factory()->create();
            }
            $patientUser = User::first();
            if (!$patientUser) {
                $patientUser = User::factory()->create();
            }

            return Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patientUser->id,
                'guest_name' => 'Test Patient',
                'appointment_date' => Carbon::now()->addDays(3),
                'status' => 'confirmed',
            ]);
        });

        return new AppointmentStatusChangedNotification($appointment, 'pending', 'confirmed', $user);
    }

    private function createWaitlistAutoBookedNotification($user)
    {
        $appointment = Appointment::withoutEvents(function () {
            $doctor = Doctor::first();
            if (!$doctor) {
                $doctor = Doctor::factory()->create();
            }
            $patientUser = User::first();
            if (!$patientUser) {
                $patientUser = User::factory()->create();
            }

            return Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patientUser->id,
                'guest_name' => 'Waitlist Test Patient',
                'appointment_date' => Carbon::now()->addDay(),
                'status' => 'confirmed',
            ]);
        });

        // WaitlistAutoBookedNotification only takes the appointment, not the user
        return new WaitlistAutoBookedNotification($appointment);
    }

    private function createWaitlistSlotAvailableNotification($user)
    {
        // Find existing waitlist entry or create a mock one
        $waitlistEntry = \App\Models\WaitlistEntry::first();
        if (!$waitlistEntry) {
            // Create minimal mock for testing - note this won't have full relationships
            $waitlistEntry = new \App\Models\WaitlistEntry([
                'id' => rand(1, 10000),
                'user_id' => $user->id,
                'position' => 1,
                'expires_at' => Carbon::now()->addDay(),
                'status' => 'waiting',
            ]);
            $waitlistEntry->exists = true;
        }

        // Attach the user relation for the notification
        $waitlistEntry->user = $user;

        return new WaitlistSlotAvailableNotification($waitlistEntry);
    }

    private function createWaitlistPositionUpdateNotification($user)
    {
        // Find or create a mock WaitlistEntry (class requires WaitlistEntry, not User)
        $waitlistEntry = WaitlistEntry::first();
        if (!$waitlistEntry) {
            $waitlistEntry = new WaitlistEntry();
            $waitlistEntry->id = rand(1, 10000);
            $waitlistEntry->user_id = $user->id;
            $waitlistEntry->status = 'waiting';
            $waitlistEntry->exists = true;
        }

        return new WaitlistPositionUpdateNotification($waitlistEntry, 5, 3);
    }

    private function createWaitlistOfferExpiringNotification($user)
    {
        // Find or create a mock WaitlistEntry (class requires WaitlistEntry, not User)
        $waitlistEntry = WaitlistEntry::first();
        if (!$waitlistEntry) {
            $waitlistEntry = new WaitlistEntry();
            $waitlistEntry->id = rand(1, 10000);
            $waitlistEntry->user_id = $user->id;
            $waitlistEntry->position = 1;
            $waitlistEntry->expires_at = Carbon::now()->addHours(2);
            $waitlistEntry->status = 'offered';
            $waitlistEntry->exists = true;
        }

        return new WaitlistOfferExpiringNotification($waitlistEntry);
    }

    private function createWaitlistExpiredNotification($user)
    {
        // Find or create a mock WaitlistEntry (class requires WaitlistEntry, not User)
        $waitlistEntry = WaitlistEntry::first();
        if (!$waitlistEntry) {
            $waitlistEntry = new WaitlistEntry();
            $waitlistEntry->id = rand(1, 10000);
            $waitlistEntry->user_id = $user->id;
            $waitlistEntry->position = 1;
            $waitlistEntry->status = 'expired';
            $waitlistEntry->exists = true;
        }

        return new WaitlistExpiredNotification($waitlistEntry);
    }

    private function createHighRiskClaimAlert($user)
    {
        return new HighRiskClaimAlert([
            'claim_id' => rand(1000, 9999),
            'claim_number' => 'CLM-' . rand(10000, 99999),
            'denial_risk' => 0.85,
            'top_factors' => ['Missing prior authorization', 'Incorrect CPT code', 'Expired referral'],
            'expected_amount' => 4500.00,
            'user_id' => $user->id,
        ]);
    }

    private function createUnderpaymentAlert($user)
    {
        return new UnderpaymentAlert([
            'alert_id' => rand(1000, 9999),
            'claim_id' => rand(1000, 9999),
            'claim_number' => 'CLM-' . rand(10000, 99999),
            'expected_amount' => 2500.00,
            'paid_amount' => 1800.00,
            'variance' => 700.00,
            'threshold_percentage' => 10,
            'user_id' => $user->id,
        ]);
    }

    private function createUrgentTaskNotification($user)
    {
        $task = WorkflowTask::withoutEvents(function () use ($user) {
            return WorkflowTask::factory()->create([
                'title' => 'URGENT: Critical Task Requiring Immediate Attention',
                'description' => 'This is a test urgent task for notification purposes',
                'due_date' => Carbon::now()->subDays(5),
                'priority' => 'urgent',
                'assigned_to' => $user->id,
                'task_type' => 'claims',
            ]);
        });

        return new UrgentTaskAlert($task);
    }

    private function createHEPSafetyAlertNotification($user)
    {
        return new HEPSafetyAlert(
            $user,
            [
                ['severity' => 'high', 'message' => 'Patient reported dizziness during exercise'],
                ['severity' => 'medium', 'message' => 'Exercise completion rate dropped below 50%'],
            ],
            'high'
        );
    }

    private function createTaskOverdueNotification($user)
    {
        $task = WorkflowTask::withoutEvents(function () use ($user) {
            return WorkflowTask::factory()->create([
                'title' => 'Overdue Task Notification',
                'description' => 'This task is overdue and needs attention',
                'due_date' => Carbon::now()->subDays(3),
                'priority' => 'high',
                'assigned_to' => $user->id,
                'task_type' => 'documentation',
            ]);
        });

        return new TaskOverdueNotification($task);
    }

    private function createTaskReminderNotification($user)
    {
        $task = WorkflowTask::withoutEvents(function () use ($user) {
            return WorkflowTask::factory()->create([
                'title' => 'Task Reminder Notification',
                'description' => 'This task is due soon',
                'due_date' => Carbon::now()->addHours(6),
                'priority' => 'medium',
                'assigned_to' => $user->id,
                'task_type' => 'follow_up',
            ]);
        });

        return new TaskReminderNotification($task);
    }

    private function createInvoiceCreatedNotification($user)
    {
        $invoice = new StripeInvoice();
        $invoice->forceFill([
            'id' => rand(900000, 999999),
            'user_id' => $user->id,
            'stripe_invoice_id' => 'inv_test_' . rand(1000, 9999),
            'amount_due' => 29900,
            'due_date' => Carbon::now()->addDays(30),
            'description' => 'Test Invoice - Notification System Test',
        ]);
        $invoice->exists = true;

        return new InvoiceCreated($invoice);
    }

    private function createInvoiceDueSoonNotification($user)
    {
        $invoice = new StripeInvoice();
        $invoice->forceFill([
            'id' => rand(900000, 999999),
            'user_id' => $user->id,
            'stripe_invoice_id' => 'inv_test_' . rand(1000, 9999),
            'amount_due' => 29900,
            'due_date' => Carbon::now()->addDays(3),
            'description' => 'Test Invoice Due Soon',
        ]);
        $invoice->exists = true;

        return new InvoiceDueSoon($invoice);
    }

    private function createInvoiceOverdueNotification($user)
    {
        $invoice = new StripeInvoice();
        $invoice->forceFill([
            'id' => rand(900000, 999999),
            'user_id' => $user->id,
            'stripe_invoice_id' => 'inv_test_' . rand(1000, 9999),
            'amount_due' => 29900,
            'due_date' => Carbon::now()->subDays(10),
            'description' => 'Test Invoice Overdue',
        ]);
        $invoice->exists = true;

        return new InvoiceOverdue($invoice);
    }

    private function createInvoiceReminderNotification($user)
    {
        $invoice = new StripeInvoice();
        $invoice->forceFill([
            'id' => rand(900000, 999999),
            'user_id' => $user->id,
            'stripe_invoice_id' => 'inv_test_' . rand(1000, 9999),
            'amount_due' => 29900,
            'due_date' => Carbon::now()->subDays(5),
            'description' => 'Test Invoice Reminder',
        ]);
        $invoice->exists = true;

        return new InvoiceReminder($invoice, 2);
    }

    private function createMonthlyInvoiceNotification($user)
    {
        $invoice = new StripeInvoice();
        $invoice->forceFill([
            'id' => rand(900000, 999999),
            'user_id' => $user->id,
            'stripe_invoice_id' => 'inv_test_' . rand(1000, 9999),
            'amount_due' => 9900,
            'due_date' => Carbon::now()->addDays(15),
            'grace_period_ends_at' => Carbon::now()->addDays(30),
            'description' => 'Monthly Subscription - Test',
        ]);
        $invoice->exists = true;

        return new MonthlyInvoiceCreated($invoice);
    }

    private function createHEPProgramGeneratedNotification($user)
    {
        $program = HepProgram::withoutEvents(function () {
            return HepProgram::factory()->create([
                'title' => 'Test HEP Program',
                'duration_weeks' => 8,
                'frequency_per_week' => 3,
            ]);
        });

        return new HEPProgramGenerated($program, 'completed');
    }

    private function createHEPExerciseReminderNotification($user)
    {
        $assignment = HepAssignment::withoutEvents(function () {
            return HepAssignment::factory()->create();
        });

        return new HEPExerciseReminder($assignment, [], 'daily');
    }

    private function createEligibilityCheckFailedNotification($user)
    {
        $insurance = \App\Models\PatientInsurance::withoutEvents(function () {
            return \App\Models\PatientInsurance::factory()->create();
        });

        return new EligibilityCheckFailedNotification(
            $insurance,
            'specialist_visit',
            'Coverage terminated for non-payment'
        );
    }

    private function createEligibilityExpiringNotification($user)
    {
        $insurance = \App\Models\PatientInsurance::withoutEvents(function () {
            return \App\Models\PatientInsurance::factory()->create();
        });

        return new EligibilityExpiringNotification($insurance, 15);
    }

    private function createSystemAlertNotification()
    {
        // SystemAlertNotification takes (string $title, string $message, string $type, array $data)
        return new SystemAlertNotification(
            'System Warning Test',
            'This is a test system alert notification',
            'warning',
            []
        );
    }

    private function createKioskOfflineNotification()
    {
        $kiosk = Kiosk::withoutEvents(function () {
            return Kiosk::factory()->create([
                'name' => 'Test Kiosk',
                'serial_number' => 'KSK-TEST-' . rand(1000, 9999),
                'location' => 'Test Location',
                'status' => 'offline',
            ]);
        });

        return new KioskOffline($kiosk);
    }

    private function createKioskSessionTimeoutNotification()
    {
        $kiosk = Kiosk::withoutEvents(function () {
            return Kiosk::factory()->create([
                'name' => 'Test Kiosk',
                'serial_number' => 'KSK-TEST-' . rand(1000, 9999),
            ]);
        });

        $session = KioskSession::withoutEvents(function () use ($kiosk) {
            return KioskSession::factory()->create([
                'kiosk_id' => $kiosk->id,
                'session_id' => 'sess_test_' . rand(1000, 9999),
                'start_time' => Carbon::now()->subMinutes(30),
            ]);
        });

        return new KioskSessionTimeout($session);
    }

    private function createAccountRestrictedNotification($user)
    {
        $setting = MonthlyInvoiceSetting::withoutEvents(function () {
            return MonthlyInvoiceSetting::factory()->create();
        });

        return new AccountRestricted($setting);
    }

    private function createFinalWarningNotification($user)
    {
        $setting = MonthlyInvoiceSetting::withoutEvents(function () {
            return MonthlyInvoiceSetting::factory()->create();
        });

        return new FinalWarning($setting);
    }

    private function createGracePeriodReminderNotification($user)
    {
        $setting = MonthlyInvoiceSetting::withoutEvents(function () {
            return MonthlyInvoiceSetting::factory()->create();
        });

        return new GracePeriodReminder($setting);
    }

    private function createPasswordResetNotification()
    {
        return new ResetPasswordNotification('test-token-' . rand(1000, 9999));
    }

    private function createVoiceTranscriptionNotification()
    {
        // VoiceTranscriptionCompletedNotification takes (VoiceTranscription $transcription)
        $transcription = VoiceTranscription::withoutEvents(function () {
            return VoiceTranscription::factory()->create([
                'session_id' => 'test-session-' . rand(1000, 9999),
                'session_started_at' => Carbon::now()->subMinutes(5),
                'session_ended_at' => Carbon::now(),
            ]);
        });

        return new VoiceTranscriptionCompletedNotification($transcription);
    }

    private function createVoicePerformanceAlertNotification()
    {
        return new VoiceAssistantPerformanceAlert(
            ['High error rate detected in transcription'],
            ['total_sessions' => 100, 'avg_processing_time' => 2.5, 'success_rate' => 78.5, 'error_rate' => 21.5]
        );
    }

    private function createDiagnosisSubmittedNotification($user)
    {
        // DiagnosisSubmittedNotification takes (Diagnosis $diagnosis)
        $diagnosis = Diagnosis::withoutEvents(function () {
            return Diagnosis::factory()->create();
        });

        return new DiagnosisSubmittedNotification($diagnosis);
    }

    private function createReviewSubmittedNotification($user)
    {
        // ReviewSubmittedNotification takes (Review $review)
        $review = Review::withoutEvents(function () {
            return Review::factory()->create();
        });

        return new ReviewSubmittedNotification($review);
    }

    private function createTestNotification()
    {
        return new TestNotification([
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'icon' => 'bell',
        ]);
    }
}
