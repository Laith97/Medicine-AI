<?php

namespace Tests\Unit\Services;

use App\Services\SmsService;
use App\Contracts\SmsProviderInterface;
use Tests\TestCase;
use Mockery;

class SmsServiceTest extends TestCase
{
    protected $smsService;
    protected $smsProviderMock;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsProviderMock = Mockery::mock(SmsProviderInterface::class);
        $this->smsService = new SmsService($this->smsProviderMock);

        $this->user = (object) [
            'phone' => '+1234567890'
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_sms_success()
    {
        $phoneNumber = '+1234567890';
        $message = 'Your appointment is confirmed for tomorrow at 2 PM.';

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($phoneNumber, $message)
            ->andReturn([
                'success' => true,
                'message_id' => 'sms_123456',
                'status' => 'sent'
            ]);

        $result = $this->smsService->sendSms($phoneNumber, $message);

        $this->assertTrue($result['success']);
        $this->assertEquals('sms_123456', $result['message_id']);
        $this->assertEquals('sent', $result['status']);
    }

    public function test_send_sms_failure()
    {
        $phoneNumber = '+1234567890';
        $message = 'Test message';

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($phoneNumber, $message)
            ->andReturn([
                'success' => false,
                'error' => 'Invalid phone number',
                'error_code' => 'INVALID_NUMBER'
            ]);

        $result = $this->smsService->sendSms($phoneNumber, $message);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid phone number', $result['error']);
        $this->assertEquals('INVALID_NUMBER', $result['error_code']);
    }

    public function test_send_appointment_reminder()
    {
        $appointmentData = [
            'patient_name' => 'John Doe',
            'doctor_name' => 'Dr. Smith',
            'appointment_date' => '2024-01-15',
            'appointment_time' => '14:00',
            'clinic_name' => 'Medical Center'
        ];

        $expectedMessage = "Reminder: You have an appointment with Dr. Smith on 2024-01-15 at 14:00 at Medical Center. Please arrive 15 minutes early.";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'reminder_123']);

        $result = $this->smsService->sendAppointmentReminder($this->user, $appointmentData);

        $this->assertTrue($result['success']);
        $this->assertEquals('reminder_123', $result['message_id']);
    }

    public function test_send_prescription_notification()
    {
        $prescriptionData = [
            'medication_name' => 'Amoxicillin',
            'dosage' => '500mg',
            'frequency' => 'twice daily',
            'duration' => '7 days',
            'pharmacy_name' => 'City Pharmacy'
        ];

        $expectedMessage = "Your prescription for Amoxicillin 500mg (twice daily for 7 days) is ready for pickup at City Pharmacy.";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'prescription_456']);

        $result = $this->smsService->sendPrescriptionNotification($this->user, $prescriptionData);

        $this->assertTrue($result['success']);
        $this->assertEquals('prescription_456', $result['message_id']);
    }

    public function test_send_test_results_notification()
    {
        $testData = [
            'test_name' => 'Blood Test',
            'status' => 'completed',
            'doctor_name' => 'Dr. Johnson',
            'portal_url' => 'https://portal.example.com'
        ];

        $expectedMessage = "Your Blood Test results are now available. Please contact Dr. Johnson or visit https://portal.example.com to view your results.";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'test_789']);

        $result = $this->smsService->sendTestResultsNotification($this->user, $testData);

        $this->assertTrue($result['success']);
        $this->assertEquals('test_789', $result['message_id']);
    }

    public function test_send_emergency_alert()
    {
        $alertData = [
            'alert_type' => 'critical_result',
            'message' => 'Please contact your doctor immediately regarding your recent test results.',
            'doctor_phone' => '+1987654321',
            'urgency' => 'high'
        ];

        $expectedMessage = "URGENT: Please contact your doctor immediately regarding your recent test results. Doctor: +1987654321";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'emergency_999']);

        $result = $this->smsService->sendEmergencyAlert($this->user, $alertData);

        $this->assertTrue($result['success']);
        $this->assertEquals('emergency_999', $result['message_id']);
    }

    public function test_validate_phone_number_valid()
    {
        $validNumbers = [
            '+1234567890',
            '+44123456789',
            '+33123456789',
            '1234567890'
        ];

        foreach ($validNumbers as $number) {
            $this->assertTrue($this->smsService->validatePhoneNumber($number));
        }
    }

    public function test_validate_phone_number_invalid()
    {
        $invalidNumbers = [
            '123',
            'abc123',
            '',
            '+',
            '123-456-7890-extra'
        ];

        foreach ($invalidNumbers as $number) {
            $this->assertFalse($this->smsService->validatePhoneNumber($number));
        }
    }

    public function test_format_phone_number()
    {
        $testCases = [
            ['1234567890', '+1234567890'],
            ['+1234567890', '+1234567890'],
            ['(123) 456-7890', '+11234567890'],
            ['123-456-7890', '+11234567890']
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = $this->smsService->formatPhoneNumber($input);
            $this->assertEquals($expected, $result);
        }
    }

    public function test_get_sms_status()
    {
        $messageId = 'sms_123456';
        $expectedStatus = [
            'message_id' => 'sms_123456',
            'status' => 'delivered',
            'delivered_at' => '2024-01-15 10:30:00'
        ];

        $this->smsProviderMock
            ->shouldReceive('getMessageStatus')
            ->once()
            ->with($messageId)
            ->andReturn($expectedStatus);

        $result = $this->smsService->getSmsStatus($messageId);

        $this->assertEquals($expectedStatus, $result);
    }

    public function test_send_bulk_sms()
    {
        $recipients = [
            ['phone' => '+1234567890', 'name' => 'John Doe'],
            ['phone' => '+1987654321', 'name' => 'Jane Smith']
        ];
        $message = 'Important health update from your medical provider.';

        $this->smsProviderMock
            ->shouldReceive('sendBulkSms')
            ->once()
            ->with($recipients, $message)
            ->andReturn([
                'success' => true,
                'sent_count' => 2,
                'failed_count' => 0,
                'message_ids' => ['bulk_123', 'bulk_124']
            ]);

        $result = $this->smsService->sendBulkSms($recipients, $message);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['sent_count']);
        $this->assertEquals(0, $result['failed_count']);
        $this->assertCount(2, $result['message_ids']);
    }

    public function test_send_medication_reminder()
    {
        $medicationData = [
            'medication_name' => 'Lisinopril',
            'dosage' => '10mg',
            'time' => '08:00',
            'instructions' => 'Take with food'
        ];

        $expectedMessage = "Medication Reminder: Time to take your Lisinopril 10mg. Instructions: Take with food";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'med_reminder_555']);

        $result = $this->smsService->sendMedicationReminder($this->user, $medicationData);

        $this->assertTrue($result['success']);
        $this->assertEquals('med_reminder_555', $result['message_id']);
    }

    public function test_send_follow_up_reminder()
    {
        $followUpData = [
            'doctor_name' => 'Dr. Brown',
            'follow_up_date' => '2024-02-01',
            'reason' => 'Check blood pressure',
            'phone_number' => '+1555123456'
        ];

        $expectedMessage = "Follow-up Reminder: Please schedule your follow-up appointment with Dr. Brown by 2024-02-01 for: Check blood pressure. Call: +1555123456";

        $this->smsProviderMock
            ->shouldReceive('send')
            ->once()
            ->with($this->user->phone, $expectedMessage)
            ->andReturn(['success' => true, 'message_id' => 'followup_777']);

        $result = $this->smsService->sendFollowUpReminder($this->user, $followUpData);

        $this->assertTrue($result['success']);
        $this->assertEquals('followup_777', $result['message_id']);
    }

    public function test_get_delivery_report()
    {
        $messageId = 'sms_123456';
        $expectedReport = [
            'message_id' => 'sms_123456',
            'status' => 'delivered',
            'delivered_at' => '2024-01-15 10:30:00',
            'delivery_attempts' => 1,
            'cost' => 0.05,
            'country_code' => 'US'
        ];

        $this->smsProviderMock
            ->shouldReceive('getDeliveryReport')
            ->once()
            ->with($messageId)
            ->andReturn($expectedReport);

        $result = $this->smsService->getDeliveryReport($messageId);

        $this->assertEquals($expectedReport, $result);
    }
}
