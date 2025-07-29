<?php

namespace Tests\Unit\Services;

use App\Services\EmailService;
use App\Models\User;
use App\Mail\ContactFormMail;
use App\Mail\SubscriptionConfirmation;
use App\Mail\UsageWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $emailService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailService = new EmailService();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
    }

    public function test_email_service_can_be_instantiated()
    {
        $this->assertInstanceOf(EmailService::class, $this->emailService);
    }

    public function test_send_contact_form_email()
    {
        Mail::fake();

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content'
        ];

        $result = $this->emailService->sendContactFormEmail($contactData);

        $this->assertTrue($result);

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['subject'] === $contactData['subject'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_send_subscription_confirmation_email()
    {
        Mail::fake();

        $subscriptionData = [
            'plan' => 'premium',
            'amount' => 25.00,
            'billing_cycle' => 'monthly'
        ];

        $result = $this->emailService->sendSubscriptionConfirmation($this->user, $subscriptionData);

        $this->assertTrue($result);

        Mail::assertSent(SubscriptionConfirmation::class, function ($mail) use ($subscriptionData) {
            return $mail->hasTo($this->user->email) &&
                   $mail->subscriptionData['plan'] === $subscriptionData['plan'] &&
                   $mail->subscriptionData['amount'] === $subscriptionData['amount'];
        });
    }

    public function test_send_usage_warning_email()
    {
        Mail::fake();

        $usageData = [
            'current_usage' => 850,
            'limit' => 1000,
            'percentage' => 85,
            'warning_type' => 'token_limit'
        ];

        $result = $this->emailService->sendUsageWarning($this->user, $usageData);

        $this->assertTrue($result);

        Mail::assertSent(UsageWarning::class, function ($mail) use ($usageData) {
            return $mail->hasTo($this->user->email) &&
                   $mail->usageData['current_usage'] === $usageData['current_usage'] &&
                   $mail->usageData['limit'] === $usageData['limit'] &&
                   $mail->usageData['warning_type'] === $usageData['warning_type'];
        });
    }

    public function test_send_password_reset_email()
    {
        Mail::fake();

        $token = 'test-reset-token';
        $result = $this->emailService->sendPasswordResetEmail($this->user, $token);

        $this->assertTrue($result);

        // Verify that a password reset notification was sent
        Mail::assertSent(function ($mail) {
            return $mail instanceof \App\Notifications\ResetPasswordNotification;
        });
    }

    public function test_send_welcome_email()
    {
        Mail::fake();

        $result = $this->emailService->sendWelcomeEmail($this->user);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Welcome');
        });
    }

    public function test_send_appointment_confirmation_email()
    {
        Mail::fake();

        $appointmentData = [
            'appointment_date' => now()->addDay(),
            'doctor_name' => 'Dr. Smith',
            'appointment_type' => 'consultation',
            'duration' => 30
        ];

        $result = $this->emailService->sendAppointmentConfirmation($this->user, $appointmentData);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($appointmentData) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Appointment Confirmation');
        });
    }

    public function test_send_appointment_reminder_email()
    {
        Mail::fake();

        $appointmentData = [
            'appointment_date' => now()->addHour(),
            'doctor_name' => 'Dr. Smith',
            'appointment_type' => 'consultation'
        ];

        $result = $this->emailService->sendAppointmentReminder($this->user, $appointmentData);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($appointmentData) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Appointment Reminder');
        });
    }

    public function test_send_invoice_notification_email()
    {
        Mail::fake();

        $invoiceData = [
            'invoice_id' => 'inv_123',
            'amount' => 50.00,
            'due_date' => now()->addWeek(),
            'invoice_url' => 'https://example.com/invoice/123'
        ];

        $result = $this->emailService->sendInvoiceNotification($this->user, $invoiceData);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($invoiceData) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Invoice');
        });
    }

    public function test_send_payment_confirmation_email()
    {
        Mail::fake();

        $paymentData = [
            'payment_id' => 'pay_123',
            'amount' => 25.00,
            'payment_date' => now(),
            'payment_method' => 'card'
        ];

        $result = $this->emailService->sendPaymentConfirmation($this->user, $paymentData);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($paymentData) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Payment Confirmation');
        });
    }

    public function test_send_account_suspension_email()
    {
        Mail::fake();

        $suspensionData = [
            'reason' => 'Overdue payment',
            'suspension_date' => now(),
            'action_required' => 'Please update your payment method'
        ];

        $result = $this->emailService->sendAccountSuspensionNotification($this->user, $suspensionData);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($suspensionData) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->build()->subject, 'Account Suspension');
        });
    }

    public function test_send_bulk_email()
    {
        Mail::fake();

        $users = User::factory()->count(3)->create();
        $subject = 'Bulk Email Test';
        $content = 'This is a bulk email test';

        $result = $this->emailService->sendBulkEmail($users, $subject, $content);

        $this->assertTrue($result);

        Mail::assertSent(function ($mail) use ($subject) {
            return str_contains($mail->build()->subject, $subject);
        }, 3); // Should be sent to 3 users
    }

    public function test_queue_email()
    {
        Mail::fake();

        $emailData = [
            'to' => $this->user->email,
            'subject' => 'Queued Email Test',
            'content' => 'This email should be queued'
        ];

        $result = $this->emailService->queueEmail($emailData);

        $this->assertTrue($result);

        // Verify email was queued (not sent immediately)
        Mail::assertQueued(function ($mail) use ($emailData) {
            return $mail->hasTo($emailData['to']) &&
                   str_contains($mail->build()->subject, $emailData['subject']);
        });
    }

    public function test_validate_email_address()
    {
        $this->assertTrue($this->emailService->validateEmailAddress('valid@example.com'));
        $this->assertTrue($this->emailService->validateEmailAddress('user.name+tag@domain.co.uk'));

        $this->assertFalse($this->emailService->validateEmailAddress('invalid-email'));
        $this->assertFalse($this->emailService->validateEmailAddress('invalid@'));
        $this->assertFalse($this->emailService->validateEmailAddress('@invalid.com'));
    }

    public function test_format_email_template()
    {
        $template = 'Hello {{name}}, your appointment is on {{date}}.';
        $variables = [
            'name' => 'John Doe',
            'date' => '2024-01-15'
        ];

        $result = $this->emailService->formatEmailTemplate($template, $variables);

        $this->assertEquals('Hello John Doe, your appointment is on 2024-01-15.', $result);
    }

    public function test_get_email_delivery_status()
    {
        // Mock email tracking
        $messageId = 'msg_123';
        $status = $this->emailService->getEmailDeliveryStatus($messageId);

        // Since we don't have actual email tracking implemented, this should return a default status
        $this->assertIsArray($status);
        $this->assertArrayHasKey('status', $status);
    }

    public function test_handle_email_failure()
    {
        Mail::fake();

        // Simulate email failure
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP connection failed'));

        $result = $this->emailService->sendContactFormEmail([
            'name' => 'Test',
            'email' => 'test@example.com',
            'subject' => 'Test',
            'message' => 'Test message'
        ]);

        $this->assertFalse($result);
    }

    public function test_email_rate_limiting()
    {
        Mail::fake();

        // Test that email service respects rate limiting
        $emails = [];
        for ($i = 0; $i < 10; $i++) {
            $emails[] = [
                'to' => "user{$i}@example.com",
                'subject' => 'Rate Limit Test',
                'content' => 'Testing rate limits'
            ];
        }

        $result = $this->emailService->sendBulkEmailWithRateLimit($emails, 5); // Limit to 5 per batch

        $this->assertTrue($result);

        // Should have sent emails in batches
        Mail::assertSent(function ($mail) {
            return str_contains($mail->build()->subject, 'Rate Limit Test');
        }, 10);
    }
}
