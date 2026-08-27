<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AdminWaitlistTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $doctorUser;
    protected Doctor $doctor;
    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $specialty = Specialty::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $specialty->id,
        ]);
        $this->patient = User::factory()->create(['role' => 'patient']);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    // === WEB PAGES ===

    public function test_admin_can_access_waitlist_dashboard()
    {
        $response = $this->actingAsAdmin()->get(route('admin.waitlist.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.waitlist.dashboard');
    }

    public function test_admin_can_access_waitlist_analytics()
    {
        $response = $this->actingAsAdmin()->get(route('admin.waitlist.analytics'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.waitlist.analytics');
    }

    public function test_admin_can_access_waitlist_manage()
    {
        $response = $this->actingAsAdmin()->get(route('admin.waitlist.manage'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.waitlist.manage');
    }

    public function test_admin_can_access_waitlist_manage_with_doctor()
    {
        $response = $this->actingAsAdmin()->get(route('admin.waitlist.manage.doctor', ['doctorId' => $this->doctor->id]));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_waitlist_dashboard()
    {
        $response = $this->get(route('admin.waitlist.dashboard'));
        $this->assertTrue(in_array($response->status(), [302, 401]));
    }

    // === API DASHBOARD ===

    public function test_api_dashboard_returns_correct_structure()
    {
        Waitlist::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => User::factory()->create(['role' => 'patient'])->id,
            'status' => 'active',
        ]);

        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/dashboard');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'statistics' => ['totalWaitlisted', 'avgWaitTime', 'fillRate', 'satisfactionScore'],
            'waitlists' => [
                '*' => ['doctor' => ['id', 'name', 'email', 'specialty'], 'patientCount', 'avgWaitTime', 'fillRate', 'priorityCases']
            ],
            'recentActivity',
        ]);
        $this->assertEquals(3, $response->json('statistics.totalWaitlisted'));
    }

    public function test_api_dashboard_empty_returns_defaults()
    {
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/dashboard');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('statistics.totalWaitlisted'));
        $this->assertEquals(0, $response->json('statistics.avgWaitTime'));
        $this->assertIsArray($response->json('waitlists'));
        $this->assertEmpty($response->json('waitlists'));
    }

    public function test_api_dashboard_groups_by_doctor()
    {
        $doctor2 = Doctor::factory()->create(['user_id' => User::factory()->create(['role'=>'doctor'])->id]);
        Waitlist::factory()->count(2)->create(['doctor_id' => $this->doctor->id, 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $doctor2->id, 'status' => 'active']);

        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/dashboard');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('waitlists'));
    }

    // === API ANALYTICS ===

    public function test_api_analytics_returns_correct_structure()
    {
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/analytics?timeframe=30');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metrics' => ['avgWaitTime', 'fillRate', 'satisfactionScore', 'priorityOverrides'],
            'charts' => [
                'waitTime' => ['labels', 'data'],
                'priority' => ['data'],
                'specialty' => ['labels', 'data'],
                'satisfaction' => ['labels', 'data'],
            ],
            'insights',
            'recommendations',
            'topPerformers',
            'bottlenecks',
        ]);
    }

    public function test_api_analytics_handles_various_timeframes()
    {
        foreach ([7, 30, 90, 365] as $tf) {
            $response = $this->actingAsAdmin()->get("/api/admin/waitlist/analytics?timeframe={$tf}");
            $response->assertStatus(200);
            $this->assertArrayHasKey('metrics', $response->json());
        }
    }

    public function test_api_analytics_normalizes_string_timeframes()
    {
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/analytics?timeframe=7days');
        $response->assertStatus(200);
        $this->assertArrayHasKey('metrics', $response->json());

        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/analytics?timeframe=invalid');
        $response->assertStatus(200);
        // Should default to 30
        $this->assertArrayHasKey('metrics', $response->json());
    }

    public function test_api_analytics_priority_data_has_four_values()
    {
        Waitlist::factory()->create(['priority_level' => 'low', 'status' => 'active']);
        Waitlist::factory()->create(['priority_level' => 'medium', 'status' => 'active']);
        Waitlist::factory()->create(['priority_level' => 'high', 'status' => 'active']);
        Waitlist::factory()->create(['priority_level' => 'urgent', 'status' => 'active']);

        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/analytics');
        $this->assertCount(4, $response->json('charts.priority.data'));
    }

    // === API MANAGE ===

    public function test_api_manage_pagination()
    {
        Waitlist::factory()->count(20)->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/manage?doctor_id='.$this->doctor->id.'&page=1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats' => ['totalPatients', 'avgWaitTime', 'priorityCases', 'fillRate'],
            'patients',
            'pagination' => ['from', 'to', 'total', 'current_page', 'last_page', 'per_page'],
        ]);
        $this->assertEquals(20, $response->json('pagination.total'));
        $this->assertCount(15, $response->json('patients')); // perPage 15
    }

    public function test_api_manage_filters_by_priority_and_status()
    {
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'urgent', 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'low', 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'urgent', 'status' => 'paused']);

        $resp = $this->actingAsAdmin()->get('/api/admin/waitlist/manage?priority=urgent');
        $this->assertEquals(2, $resp->json('stats.totalPatients'));
        $this->assertEquals(2, count($resp->json('patients')));

        $resp2 = $this->actingAsAdmin()->get('/api/admin/waitlist/manage?status=active');
        $this->assertEquals(2, $resp2->json('stats.totalPatients'));
    }

    public function test_api_manage_search_by_patient_name()
    {
        $p = User::factory()->create(['role' => 'patient', 'name' => 'UniqueSearchName123']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'patient_id' => $p->id, 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'status' => 'active']);

        $resp = $this->actingAsAdmin()->get('/api/admin/waitlist/manage?search=UniqueSearchName');
        $this->assertEquals(1, $resp->json('stats.totalPatients'));
    }

    public function test_api_manage_stats_priorityCases_counts_high_and_urgent()
    {
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'high', 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'urgent', 'status' => 'active']);
        Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'priority_level' => 'low', 'status' => 'active']);

        $resp = $this->actingAsAdmin()->get('/api/admin/waitlist/manage');
        $this->assertEquals(2, $resp->json('stats.priorityCases'));
    }

    // === MUTATIONS ===

    public function test_api_assign_slot_creates_entry()
    {
        $w = Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id, 'status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/assign-slot', ['patientId' => $w->id]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlist_entries', ['waitlist_id' => $w->id, 'status' => 'offered']);
    }

    public function test_api_remove_patient_cancels_waitlist()
    {
        $w = Waitlist::factory()->create(['doctor_id' => $this->doctor->id, 'status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/remove-patient', ['patientId' => $w->id]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'status' => 'cancelled']);
    }

    public function test_api_update_priority()
    {
        $w = Waitlist::factory()->create(['priority_level' => 'low', 'status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/update-priority', ['patientId' => $w->id, 'priority' => 'urgent']);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'priority_level' => 'urgent']);
    }

    public function test_api_update_status()
    {
        $w = Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/update-status', ['patientId' => $w->id, 'status' => 'paused']);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'status' => 'paused']);
    }

    public function test_api_bulk_update_priority()
    {
        $w1 = Waitlist::factory()->create(['priority_level' => 'low', 'status' => 'active']);
        $w2 = Waitlist::factory()->create(['priority_level' => 'low', 'status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/bulk-update', [
            'patientIds' => [$w1->id, $w2->id],
            'action' => 'priority',
            'value' => 'high',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w1->id, 'priority_level' => 'high']);
        $this->assertDatabaseHas('waitlists', ['id' => $w2->id, 'priority_level' => 'high']);
    }

    public function test_api_bulk_update_status()
    {
        $w1 = Waitlist::factory()->create(['status' => 'active']);
        $w2 = Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/bulk-update', [
            'patientIds' => [$w1->id, $w2->id],
            'action' => 'status',
            'value' => 'paused',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('waitlists', ['id' => $w1->id, 'status' => 'paused']);
    }

    public function test_api_bulk_update_remove()
    {
        $w = Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/bulk-update', [
            'patientIds' => [$w->id],
            'action' => 'remove',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'status' => 'cancelled']);
    }

    public function test_api_doctors_returns_list()
    {
        $response = $this->actingAsAdmin()->get('/api/admin/doctors');
        $response->assertStatus(200);
        $response->assertJsonStructure(['doctors' => [['id', 'name', 'email']]]);
        $this->assertGreaterThanOrEqual(1, count($response->json('doctors')));
    }

    public function test_api_patients_returns_active_waitlists()
    {
        Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/patients');
        $response->assertStatus(200);
        $response->assertJsonStructure(['patients']);
        $this->assertGreaterThanOrEqual(1, count($response->json('patients')));
    }

    public function test_api_priority_data()
    {
        Waitlist::factory()->create(['status' => 'active', 'priority_level' => 'urgent']);
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/priority-data');
        $response->assertStatus(200);
        $response->assertJsonStructure(['patients' => [['id', 'name', 'currentPriority']]]);
    }

    public function test_api_export_returns_csv()
    {
        Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/export');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_api_analytics_export_returns_csv()
    {
        $response = $this->actingAsAdmin()->get('/api/admin/waitlist/analytics/export?timeframe=30');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_api_bulk_priority()
    {
        $w = Waitlist::factory()->create(['status' => 'active', 'priority_level' => 'low']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/bulk-priority', [
            'patientIds' => [$w->id],
            'priority' => 'high',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'priority_level' => 'high']);
    }

    public function test_api_bulk_status()
    {
        $w = Waitlist::factory()->create(['status' => 'active']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/bulk-status', [
            'patientIds' => [$w->id],
            'status' => 'paused',
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'status' => 'paused']);
    }

    public function test_api_guest_unauthenticated_fails()
    {
        $response = $this->get('/api/admin/waitlist/dashboard');
        $this->assertTrue(in_array($response->status(), [302, 401]));
    }

    public function test_api_force_assign()
    {
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/force-assign', ['waitlist_id' => 999]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_api_priority_adjustments()
    {
        $w = Waitlist::factory()->create(['status' => 'active', 'priority_level' => 'low']);
        $response = $this->actingAsAdmin()->postJson('/api/admin/waitlist/priority-adjustments', [
            'changes' => [['id' => $w->id, 'priority' => 'urgent']],
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('waitlists', ['id' => $w->id, 'priority_level' => 'urgent']);
    }
}
