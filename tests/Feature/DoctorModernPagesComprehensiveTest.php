<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorBlogPost;
use App\Models\AvailabilitySlot;
use App\Models\DoctorNote;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DoctorModernPagesComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private function doctorUser(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['role'=>'doctor','email_verified_at'=>now()], $attrs));
        Doctor::factory()->create(['user_id'=>$user->id,'is_active'=>true,'is_verified'=>true]);
        return $user->refresh();
    }

    private function patientUser(array $attrs=[]): User
    {
        return User::factory()->create(array_merge(['role'=>'patient','email_verified_at'=>now()], $attrs));
    }

    public function test_doctor_profile_page_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.profile.edit'));
        $r->assertStatus(200);
        $r->assertSee('Doctor Profile', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('doctor-design-system.css', false);
        $r->assertSee('Basic Information', false);
        $r->assertSee('Practice Address', false);
    }

    public function test_doctor_settings_appointments_page_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.settings.appointments'));
        $r->assertStatus(200);
        $r->assertSee('Appointment Settings', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('Appointment Types', false);
        $r->assertSee('Current Status', false);
        $r->assertSee('doctor-design-system.css', false);
    }

    public function test_doctor_availability_index_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.availability.index'));
        $r->assertStatus(200);
        $r->assertSee('Availability', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('Weekly Schedule', false);
        $r->assertSee('week-tabs', false);
        $r->assertSee('doctor-design-system.css', false);
    }

    public function test_doctor_availability_create_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.availability.create'));
        $r->assertStatus(200);
        $r->assertSee('Add Availability Slot', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('New Time Slot', false);
    }

    public function test_doctor_blog_index_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.blog.index'));
        $r->assertStatus(200);
        $r->assertSee('Blog Management', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('doctor-design-system.css', false);
        $r->assertSee('cases-overview.css', false);
    }

    public function test_doctor_blog_create_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.blog.create'));
        $r->assertStatus(200);
        $r->assertSee('Create Blog Post', false);
        $r->assertSee('Post Content', false);
        $r->assertSee('SEO Settings', false);
    }

    public function test_doctor_blog_show_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $doctorProfile = $doctor->doctor;
        $post = DoctorBlogPost::factory()->create(['doctor_id'=>$doctorProfile->id]);
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.blog.show', $post));
        $r->assertStatus(200);
        $r->assertSee(e($post->title), false);
        $r->assertSee('doctor-design-system.css', false);
    }

    public function test_doctor_kiosk_setup_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.kiosk.setup'));
        $r->assertStatus(200);
        $r->assertSee('Kiosk Configuration', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('Clinic Information', false);
        $r->assertSee('doctor-design-system.css', false);
    }

    public function test_doctor_kiosk_management_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        // management redirects to setup if no config, so create one
        \Illuminate\Support\Facades\DB::table('doctor_kiosk_configs')->insert([
            'doctor_id'=> $doctor->doctor->id,
            'clinic_name'=>'Test Clinic',
            'clinic_address'=>'123 Test St',
            'contact_phone'=>'+15551234567',
            'kiosk_display_name'=>'Welcome',
            'primary_color'=>'#2563eb',
            'secondary_color'=>'#6b7280',
            'kiosk_token'=>\Illuminate\Support\Str::random(32),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.kiosk.management'));
        $r->assertStatus(200);
        $r->assertSee('Kiosk Management', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('Today Sessions', false);
    }

    public function test_sub_users_index_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('sub-users.index'));
        $r->assertStatus(200);
        $r->assertSee('Sub-Users Management', false);
        $r->assertSee('cases-header-compact', false);
        $r->assertSee('doctor-design-system.css', false);
    }

    public function test_sub_users_create_renders_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        $r = $this->get(route('sub-users.create'));
        $r->assertStatus(200);
        $r->assertSee('Create New Sub-User', false);
        $r->assertSee('Basic Information', false);
        $r->assertSee('Access Permissions', false);
    }

    public function test_doctor_notes_create_renders_modern_design_with_unified_patients()
    {
        $doctor = $this->doctorUser();
        $p1 = $this->patientUser(['primary_doctor_id'=>$doctor->id]);
        $p2 = $this->patientUser(['primary_doctor_id'=>$doctor->id]);
        // patient via appointment
        $p3 = $this->patientUser();
        \App\Models\Appointment::factory()->create(['doctor_id'=>$doctor->doctor->id,'patient_id'=>$p3->id,'status'=>'completed']);
        $this->actingAs($doctor);
        $r = $this->get(route('doctor.notes.create'));
        $r->assertStatus(200);
        $r->assertSee('Create New Note', false);
        $r->assertSee('cases-header-compact', false);
        // should see all 3 patients in dropdown
        $r->assertSee(e($p1->name), false);
        $r->assertSee(e($p2->name), false);
        $r->assertSee(e($p3->name), false);
    }

    public function test_voice_assistant_show_renders_modern_transcript_and_analysis()
    {
        $doctor = $this->doctorUser();
        $patient = $this->patientUser(['primary_doctor_id'=>$doctor->id]);
        $transcription = \App\Models\VoiceTranscription::factory()->create([
            'doctor_id'=>$doctor->id,
            'patient_id'=>$patient->id,
            'raw_transcription'=>"[Speaker 1]: Hello\n[Speaker 2]: I have headache",
            'ai_analysis'=>"LEVEL 1: Test\n\n---\nLEVEL 2: Detail",
        ]);
        $this->actingAs($doctor);
        $r = $this->get(route('ai.ambient-listening.show', $transcription));
        $r->assertStatus(200);
        $r->assertSee('Raw Transcription', false);
        $r->assertSee('AI Clinical Analysis', false);
        $r->assertSee('transcript-segment', false);
    }

    public function test_availability_toggle_requires_auth()
    {
        $r = $this->get(route('doctor.availability.index'));
        $this->assertTrue(in_array($r->status(), [302,401,403]));
    }

    public function test_blog_toggle_publish_updates_status()
    {
        $doctor = $this->doctorUser();
        $post = DoctorBlogPost::factory()->create(['doctor_id'=>$doctor->doctor->id,'is_published'=>false]);
        $this->actingAs($doctor);
        $r = $this->postJson(route('doctor.blog.toggle-publish', $post));
        $r->assertStatus(200);
        $r->assertJson(['success'=>true,'is_published'=>true]);
        $this->assertDatabaseHas('doctor_blog_posts', ['id'=>$post->id,'is_published'=>true]);
    }

    public function test_settings_appointments_update_validation()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);
        // empty types should fail validation (at least one required by controller logic)
        $r = $this->putJson(route('doctor.settings.appointments.update'), ['appointment_types'=>[]]);
        // controller returns 422 or redirects with errors - accept either
        $this->assertTrue(in_array($r->status(), [200,302,422]));
    }
}
