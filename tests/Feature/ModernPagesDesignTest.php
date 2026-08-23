<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModernPagesDesignTest extends TestCase
{
    use RefreshDatabase;

    private function doctorUser(): User
    {
        $user = User::factory()->create([
            'role' => 'doctor',
            'email_verified_at' => now(),
        ]);
        \App\Models\Doctor::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'is_verified' => true,
        ]);
        return $user->refresh();
    }

    /** @test */
    public function clinical_monitoring_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('clinical.monitoring'));
        $response->assertStatus(200);
        $response->assertSee('Clinical Monitoring');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('Clinical Monitoring', false);
        // modern card wrappers
        $response->assertSee('clinical-card', false);
        $response->assertSee('LIVE', false);
    }

    /** @test */
    public function doctor_notes_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('doctor.notes.index'));
        $response->assertStatus(200);
        $response->assertSee('Doctor Notes');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('Search & Filters', false);
        $response->assertSee('doctor-dashboard.css', false);
        $response->assertSee('cases-overview.css', false);
    }

    /** @test */
    public function doctor_chat_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('doctor.chat.index'));
        $response->assertStatus(200);
        $response->assertSee('Doctor Chat');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('Live Chat', false);
    }

    /** @test */
    public function doctor_analytics_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('doctor.analytics.index'));
        $response->assertStatus(200);
        $response->assertSee('Analytics');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('cases-overview.css', false);
    }

    /** @test */
    public function doctor_reviews_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('doctor.reviews.index'));
        $response->assertStatus(200);
        $response->assertSee('Reviews');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('Average Rating', false);
    }

    /** @test */
    public function doctor_testimonials_page_renders_with_modern_design()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        $response = $this->get(route('doctor.testimonials.index'));
        $response->assertStatus(200);
        $response->assertSee('Testimonials');
        $response->assertSee('cases-header-compact', false);
        $response->assertSee('Public Testimonials', false);
    }

    /** @test */
    public function all_modern_pages_require_authentication()
    {
        $routes = [
            route('clinical.monitoring'),
            route('doctor.notes.index'),
            route('doctor.chat.index'),
            route('doctor.analytics.index'),
            route('doctor.reviews.index'),
            route('doctor.testimonials.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            // should redirect to login or 401/403 for guests
            $this->assertTrue(in_array($response->status(), [302, 401, 403]),
                "Route $url should require auth, got {$response->status()}");
        }
    }

    /** @test */
    public function modern_pages_preserve_core_functionality()
    {
        $doctor = $this->doctorUser();
        $this->actingAs($doctor);

        // Notes page should still have filter inputs
        $this->get(route('doctor.notes.index'))->assertSee('filter-search', false);
        // Chat should have sessions list
        $this->get(route('doctor.chat.index'))->assertSee('chat-sessions-list', false);
        // Analytics should have chart canvas
        $this->get(route('doctor.analytics.index'))->assertSee('visitsChart', false);
        // Reviews should have filter
        $this->get(route('doctor.reviews.index'))->assertSee('Filter Reviews', false);
        // Testimonials should have toggle buttons
        $this->get(route('doctor.testimonials.index'))->assertSee('toggle-public-btn', false);
    }
}
