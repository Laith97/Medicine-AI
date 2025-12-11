<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctor = User::factory()->create(['role' => 'doctor']);

        $doctorProfile = new Doctor();
        $doctorProfile->user_id = $this->doctor->id;
        $doctorProfile->save();
    }

    public function test_api_responses_include_accessibility_metadata()
    {
        $this->actingAs($this->patient);

        // Test appointment list endpoint
        $response = $this->get('/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();

        // Should include accessibility metadata
        $this->assertArrayHasKey('accessibility', $data);
        $this->assertArrayHasKey('aria_labels', $data['accessibility']);
        $this->assertArrayHasKey('screen_reader_text', $data['accessibility']);

        // Test user profile endpoint
        $response = $this->get('/api/user/profile');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('accessibility', $data);
    }

    public function test_color_contrast_and_visual_accessibility()
    {
        // Test that API responses include color contrast information
        $this->actingAs($this->patient);

        $response = $this->get('/api/ui/accessibility-settings');
        $response->assertStatus(200);

        $data = $response->json();

        // Should include color contrast settings
        $this->assertArrayHasKey('color_contrast', $data);
        $this->assertArrayHasKey('high_contrast_mode', $data);
        $this->assertArrayHasKey('color_blind_friendly', $data);

        // Test contrast ratios meet WCAG standards
        $this->assertGreaterThanOrEqual(4.5, $data['color_contrast']['normal_text']);
        $this->assertGreaterThanOrEqual(3.0, $data['color_contrast']['large_text']);
    }

    public function test_keyboard_navigation_support()
    {
        $this->actingAs($this->patient);

        // Test that forms and interactive elements support keyboard navigation
        $response = $this->get('/api/forms/appointment-booking');
        $response->assertStatus(200);

        $data = $response->json();

        // Should include keyboard navigation metadata
        $this->assertArrayHasKey('keyboard_navigation', $data);
        $this->assertArrayHasKey('tab_order', $data['keyboard_navigation']);
        $this->assertArrayHasKey('focus_management', $data['keyboard_navigation']);
        $this->assertArrayHasKey('keyboard_shortcuts', $data['keyboard_navigation']);

        // Test keyboard shortcuts are documented
        $shortcuts = $data['keyboard_navigation']['keyboard_shortcuts'];
        $this->assertArrayHasKey('save', $shortcuts);
        $this->assertArrayHasKey('cancel', $shortcuts);
        $this->assertArrayHasKey('next_field', $shortcuts);
    }

    public function test_screen_reader_compatibility()
    {
        $this->actingAs($this->patient);

        // Test appointment details include screen reader text
        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'reason' => 'Regular checkup'
        ];

        $response = $this->post('/api/appointments', $appointmentData);
        $response->assertStatus(201);

        $appointment = \App\Models\Appointment::latest()->first();

        $response = $this->get("/api/appointments/{$appointment->id}");
        $response->assertStatus(200);

        $data = $response->json();

        // Should include screen reader compatible descriptions
        $this->assertArrayHasKey('screen_reader', $data);
        $this->assertArrayHasKey('description', $data['screen_reader']);
        $this->assertArrayHasKey('status_announcement', $data['screen_reader']);
        $this->assertArrayHasKey('context_help', $data['screen_reader']);

        // Screen reader text should be descriptive
        $this->assertStringContainsString('appointment with', $data['screen_reader']['description']);
        $this->assertStringContainsString('status', $data['screen_reader']['status_announcement']);
    }

    public function test_alternative_text_and_descriptions()
    {
        $this->actingAs($this->patient);

        // Test that medical images/documents include alt text
        $response = $this->get('/api/medical-records');
        $response->assertStatus(200);

        $data = $response->json();

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $document) {
                $this->assertArrayHasKey('alt_text', $document);
                $this->assertArrayHasKey('long_description', $document);
                $this->assertNotEmpty($document['alt_text']);
            }
        }

        // Test prescription information includes accessible descriptions
        $response = $this->get('/api/patient/prescriptions');
        $response->assertStatus(200);

        $data = $response->json();

        if (isset($data['prescriptions'])) {
            foreach ($data['prescriptions'] as $prescription) {
                $this->assertArrayHasKey('accessibility', $prescription);
                $this->assertArrayHasKey('medication_descriptions', $prescription['accessibility']);

                foreach ($prescription['accessibility']['medication_descriptions'] as $med) {
                    $this->assertArrayHasKey('spoken_name', $med);
                    $this->assertArrayHasKey('dosage_description', $med);
                    $this->assertArrayHasKey('timing_description', $med);
                }
            }
        }
    }

    public function test_error_messages_accessibility()
    {
        $this->actingAs($this->patient);

        // Test validation errors include accessibility information
        $invalidAppointmentData = [
            'doctor_id' => 99999, // Invalid doctor ID
            'appointment_date' => 'invalid-date',
            'appointment_type' => ''
        ];

        $response = $this->post('/api/appointments', $invalidAppointmentData);
        $response->assertStatus(422);

        $data = $response->json();

        // Should include accessible error information
        $this->assertArrayHasKey('accessibility', $data);
        $this->assertArrayHasKey('error_announcements', $data['accessibility']);
        $this->assertArrayHasKey('field_errors', $data['accessibility']);

        // Each error should have screen reader text
        foreach ($data['accessibility']['error_announcements'] as $error) {
            $this->assertArrayHasKey('screen_reader_text', $error);
            $this->assertArrayHasKey('severity', $error);
            $this->assertArrayHasKey('field_id', $error);
        }
    }

    public function test_focus_management_and_indicators()
    {
        $this->actingAs($this->patient);

        // Test that focus indicators are properly managed
        $response = $this->get('/api/ui/focus-management');
        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('focus_indicators', $data);
        $this->assertArrayHasKey('visible_focus', $data['focus_indicators']);
        $this->assertArrayHasKey('focus_trapping', $data['focus_indicators']);
        $this->assertArrayHasKey('focus_restoration', $data['focus_indicators']);

        // Test modal dialogs properly trap focus
        $response = $this->get('/api/ui/modal-focus-test');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['focus_trapped']);
        $this->assertArrayHasKey('focusable_elements', $data);
    }

    public function test_responsive_design_accessibility()
    {
        // Test that content is accessible across different screen sizes
        $screenSizes = ['mobile', 'tablet', 'desktop', 'large_desktop'];

        foreach ($screenSizes as $size) {
            $response = $this->get("/api/ui/responsive-test?screen_size={$size}");
            $response->assertStatus(200);

            $data = $response->json();

            // Should include responsive accessibility checks
            $this->assertArrayHasKey('touch_targets', $data);
            $this->assertArrayHasKey('readable_text', $data);
            $this->assertArrayHasKey('navigation_accessible', $data);

            // Touch targets should meet minimum size requirements
            $this->assertGreaterThanOrEqual(44, $data['touch_targets']['minimum_size']); // 44px minimum

            // Text should be readable
            $this->assertGreaterThanOrEqual(14, $data['readable_text']['minimum_font_size']);
        }
    }

    public function test_time_based_media_accessibility()
    {
        $this->actingAs($this->patient);

        // Test that video/audio content includes accessibility features
        $response = $this->get('/api/medical-videos');
        $response->assertStatus(200);

        $data = $response->json();

        if (isset($data['videos'])) {
            foreach ($data['videos'] as $video) {
                $this->assertArrayHasKey('captions', $video);
                $this->assertArrayHasKey('transcript', $video);
                $this->assertArrayHasKey('audio_description', $video);
                $this->assertArrayHasKey('sign_language', $video);

                // Captions should be available
                $this->assertTrue($video['captions']['available']);
                $this->assertArrayHasKey('languages', $video['captions']);
            }
        }

        // Test voice message accessibility
        $response = $this->get('/api/voice-messages');
        $response->assertStatus(200);

        $data = $response->json();

        if (isset($data['messages'])) {
            foreach ($data['messages'] as $message) {
                $this->assertArrayHasKey('transcription', $message);
                $this->assertArrayHasKey('text_alternative', $message);
                $this->assertArrayHasKey('speaker_identification', $message);
            }
        }
    }

    public function test_cognitive_accessibility()
    {
        $this->actingAs($this->patient);

        // Test that complex forms are broken down into manageable steps
        $response = $this->get('/api/forms/health-assessment');
        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('progress_indicators', $data);
        $this->assertArrayHasKey('step_by_step_guidance', $data);
        $this->assertArrayHasKey('plain_language', $data);

        // Progress indicators should be clear
        $this->assertArrayHasKey('current_step', $data['progress_indicators']);
        $this->assertArrayHasKey('total_steps', $data['progress_indicators']);
        $this->assertArrayHasKey('percentage_complete', $data['progress_indicators']);

        // Language should be clear and simple
        $this->assertArrayHasKey('reading_level', $data['plain_language']);
        $this->assertLessThanOrEqual(8, $data['plain_language']['reading_level']); // 8th grade or lower
    }

    public function test_motor_disability_accessibility()
    {
        $this->actingAs($this->patient);

        // Test extended timing for user interactions
        $response = $this->get('/api/ui/timing-controls');
        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('time_limits', $data);
        $this->assertArrayHasKey('pause_resume', $data);
        $this->assertArrayHasKey('extend_time', $data);

        // Time limits should be adjustable
        $this->assertGreaterThanOrEqual(20, $data['time_limits']['minimum_seconds']);
        $this->assertTrue($data['time_limits']['adjustable']);

        // Test gesture alternatives
        $response = $this->get('/api/ui/gesture-alternatives');
        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('touch_alternatives', $data);
        $this->assertArrayHasKey('gesture_equivalents', $data);
        $this->assertArrayHasKey('single_pointer_access', $data);
    }

    public function test_multi_language_accessibility()
    {
        // Test that the application supports multiple languages for accessibility
        $languages = ['en', 'es', 'fr', 'ar', 'zh'];

        foreach ($languages as $lang) {
            $response = $this->get("/api/ui/language-support?lang={$lang}");
            $response->assertStatus(200);

            $data = $response->json();

            $this->assertArrayHasKey('rtl_support', $data);
            $this->assertArrayHasKey('translated_labels', $data);
            $this->assertArrayHasKey('localized_formats', $data);

            // Essential accessibility terms should be translated
            $essentialTerms = ['save', 'cancel', 'error', 'success', 'loading'];
            foreach ($essentialTerms as $term) {
                $this->assertArrayHasKey($term, $data['translated_labels']);
                $this->assertNotEmpty($data['translated_labels'][$term]);
            }
        }
    }

    public function test_accessibility_audit_compliance()
    {
        // Comprehensive accessibility audit
        $response = $this->get('/api/accessibility/audit');
        $response->assertStatus(200);

        $audit = $response->json();

        // WCAG 2.1 AA compliance checks
        $this->assertArrayHasKey('wcag_compliance', $audit);
        $this->assertArrayHasKey('level_a_passed', $audit['wcag_compliance']);
        $this->assertArrayHasKey('level_aa_passed', $audit['wcag_compliance']);

        // Section 508 compliance
        $this->assertArrayHasKey('section_508_compliance', $audit);

        // Automated testing results
        $this->assertArrayHasKey('automated_tests', $audit);
        $this->assertArrayHasKey('color_contrast_passed', $audit['automated_tests']);
        $this->assertArrayHasKey('keyboard_navigation_passed', $audit['automated_tests']);
        $this->assertArrayHasKey('screen_reader_compatibility_passed', $audit['automated_tests']);
        $this->assertArrayHasKey('focus_management_passed', $audit['automated_tests']);

        // Manual testing requirements
        $this->assertArrayHasKey('manual_tests_required', $audit);
        $this->assertArrayHasKey('cognitive_load_assessment', $audit['manual_tests_required']);
        $this->assertArrayHasKey('motor_skill_assessment', $audit['manual_tests_required']);
    }
}
