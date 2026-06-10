<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IndependentNotificationService
{
    /**
     * Send all notifications independently
     * If one fails, others still work
     */
    public function sendAppointmentNotifications(Appointment $appointment)
    {
        // 1. ALWAYS send real-time notification first (most important)
        $this->sendRealtimeNotification($appointment);
        
        // 2. Try email separately (won't affect real-time if it fails)
        $this->sendEmailNotification($appointment);
        
        // 3. Try SMS separately (won't affect others if it fails)
        $this->sendSmsNotification($appointment);
    }
    
    /**
     * Send real-time notification (database + broadcast)
     * This is the most critical and reliable
     */
    private function sendRealtimeNotification(Appointment $appointment)
    {
        try {
            Log::info('🔔 Sending real-time notification', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor->user_id
            ]);
            
            $doctor = $appointment->doctor->user;
            if ($doctor && $doctor->wantsNotification('appointment_booked')) {
                $doctor->notify(new \App\Notifications\ReliableAppointmentBookedNotification($appointment));
                Log::info('✅ Real-time notification sent successfully');
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Real-time notification failed: ' . $e->getMessage());
            // Don't throw - this is the most important notification
        }
    }
    
    /**
     * Send email notification independently
     * If this fails, real-time still works
     */
    private function sendEmailNotification(Appointment $appointment)
    {
        try {
            $doctor = $appointment->doctor->user;
            
            if ($doctor && $doctor->wantsNotificationChannel('email')) {
                // Validate email first
                if (!filter_var($doctor->email, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Invalid email address for doctor: ' . $doctor->email);
                    return;
                }
                
                // Send email using Mail facade (more reliable)
                Mail::to($doctor->email)->send(new \App\Mail\AppointmentBookedMail($appointment));
                Log::info('✅ Email notification sent successfully');
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Email notification failed: ' . $e->getMessage());
            // Don't throw - email failure shouldn't affect real-time
        }
    }
    
    /**
     * Send SMS notification independently
     * If this fails, real-time and email still work
     */
    private function sendSmsNotification(Appointment $appointment)
    {
        try {
            $doctor = $appointment->doctor->user;
            
            if ($doctor && $doctor->wantsNotificationChannel('sms') && $doctor->phone) {
                // Use SMS service
                $smsService = app(\App\Services\SmsService::class);
                $message = "New appointment booked with {$appointment->patient->name} on {$appointment->appointment_date->format('M j, Y g:i A')}";
                
                $smsService->send($doctor->phone, $message);
                Log::info('✅ SMS notification sent successfully');
            }
            
        } catch (\Exception $e) {
            Log::error('❌ SMS notification failed: ' . $e->getMessage());
            // Don't throw - SMS failure shouldn't affect others
        }
    }
}