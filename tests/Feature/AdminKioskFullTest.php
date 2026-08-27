<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Models\KioskCheckin;
use App\Models\KioskPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class AdminKioskFullTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin_kiosk_'.Str::random(6).'@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }

    // ==================== Model ====================
    public function test_kiosk_model_fillable_and_casts()
    {
        $k = Kiosk::factory()->create([
            'configuration' => ['a' => 1],
            'last_ping' => now(),
        ]);
        $this->assertIsArray($k->configuration);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $k->last_ping);
    }

    public function test_kiosk_scopes_and_helpers()
    {
        Kiosk::factory()->create(['status' => 'active', 'last_ping' => now()]);
        Kiosk::factory()->create(['status' => 'inactive', 'last_ping' => now()->subMinutes(10)]);
        $this->assertEquals(1, Kiosk::active()->count());
        $this->assertEquals(1, Kiosk::inactive()->count());
        $active = Kiosk::active()->first();
        $this->assertTrue($active->isActive());
        $this->assertTrue($active->isOnline());
        $inactive = Kiosk::inactive()->first();
        $this->assertFalse($inactive->isActive());
        $this->assertFalse($inactive->isOnline());
        // offline when last_ping >5 min
        $old = Kiosk::factory()->create(['status' => 'active', 'last_ping' => now()->subMinutes(10)]);
        $this->assertFalse($old->isOnline());
        $old->updateLastPing();
        $this->assertTrue($old->fresh()->isOnline());
    }

    public function test_kiosk_relationships()
    {
        $k = Kiosk::factory()->create();
        $s = KioskSession::factory()->create(['kiosk_id' => $k->id]);
        $this->assertTrue($k->sessions()->where('session_id', $s->session_id)->exists());
    }

    // ==================== Controller: Auth ====================
    public function test_kiosk_index_requires_admin_auth()
    {
        $this->get(route('admin.kiosks.index'))->assertRedirect(route('admin.login'));
    }

    public function test_kiosk_index_renders_with_admin()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.kiosks.index'))
            ->assertOk()
            ->assertViewIs('admin.kiosks.index')
            ->assertSee('Kiosks');
    }

    public function test_kiosk_create_renders()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.kiosks.create'))
            ->assertOk()
            ->assertViewIs('admin.kiosks.create');
    }

    // ==================== Store ====================
    public function test_kiosk_store_valid()
    {
        $this->actingAs($this->admin, 'admin');
        $payload = [
            'name' => 'Reception Kiosk',
            'location' => 'Main Lobby',
            'serial_number' => 'KSK-TEST-'.Str::random(6),
        ];
        $resp = $this->postJson(route('admin.kiosks.store'), $payload);
        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('kiosks', ['name' => 'Reception Kiosk', 'serial_number' => $payload['serial_number']]);
    }

    public function test_kiosk_store_validation_fails_missing_name()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->postJson(route('admin.kiosks.store'), [
            'location' => 'Lobby',
            'serial_number' => 'KSK-'.Str::random(6),
        ]);
        $resp->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_kiosk_store_duplicate_serial_fails()
    {
        $this->actingAs($this->admin, 'admin');
        $k = Kiosk::factory()->create();
        $resp = $this->postJson(route('admin.kiosks.store'), [
            'name' => 'Another',
            'serial_number' => $k->serial_number,
        ]);
        $resp->assertStatus(422);
    }

    public function test_kiosk_store_requires_admin()
    {
        $resp = $this->postJson(route('admin.kiosks.store'), [
            'name' => 'X', 'serial_number' => 'KSK-X',
        ]);
        $this->assertTrue(in_array($resp->status(), [401,302,404]), 'Expected auth failure, got '.$resp->status());
    }

    // ==================== Show / Edit ====================
    public function test_kiosk_show_renders()
    {
        $k = Kiosk::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.kiosks.show', $k))
            ->assertOk()
            ->assertViewIs('admin.kiosks.show')
            ->assertSee($k->name);
    }

    public function test_kiosk_edit_renders()
    {
        $k = Kiosk::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.kiosks.edit', $k))
            ->assertOk()
            ->assertViewIs('admin.kiosks.edit');
    }

    // ==================== Update ====================
    public function test_kiosk_update_valid()
    {
        $this->actingAs($this->admin, 'admin');
        $k = Kiosk::factory()->create(['name' => 'Old', 'status' => 'inactive']);
        $resp = $this->putJson(route('admin.kiosks.update', $k), [
            'name' => 'New Name',
            'location' => 'New Loc',
            'status' => 'active',
        ]);
        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('kiosks', ['id' => $k->id, 'name' => 'New Name', 'status' => 'active']);
    }

    public function test_kiosk_update_invalid_status()
    {
        $this->actingAs($this->admin, 'admin');
        $k = Kiosk::factory()->create();
        $this->putJson(route('admin.kiosks.update', $k), [
            'name' => 'X', 'status' => 'bad',
        ])->assertStatus(422);
    }

    // ==================== Destroy ====================
    public function test_kiosk_destroy_without_active_sessions()
    {
        $this->actingAs($this->admin, 'admin');
        $k = Kiosk::factory()->create();
        $resp = $this->deleteJson(route('admin.kiosks.destroy', $k));
        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('kiosks', ['id' => $k->id]);
    }

    public function test_kiosk_destroy_blocks_with_active_sessions()
    {
        $this->actingAs($this->admin, 'admin');
        $k = Kiosk::factory()->create();
        KioskSession::factory()->create(['kiosk_id' => $k->id, 'status' => 'active']);
        $resp = $this->deleteJson(route('admin.kiosks.destroy', $k));
        $resp->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseHas('kiosks', ['id' => $k->id]);
    }

    // ==================== Statistics ====================
    public function test_kiosk_statistics_returns_counts()
    {
        $this->actingAs($this->admin, 'admin');
        Kiosk::factory()->count(2)->create(['status' => 'active', 'last_ping' => now()]);
        Kiosk::factory()->create(['status' => 'inactive']);
        KioskSession::factory()->create(['start_time' => now(), 'status' => 'active']);
        $resp = $this->getJson(route('admin.kiosks.statistics'));
        if($resp->status() !== 200){
            dump(['status'=>$resp->status(),'content'=>$resp->getContent(), 'route'=>route('admin.kiosks.statistics')]);
        }
        $resp->assertOk()->assertJsonStructure(['success','data'=>['total_kiosks','active_kiosks','online_kiosks']]);
        $this->assertTrue($resp->json('data.total_kiosks') >= 3);
    }

    public function test_kiosk_statistics_requires_admin()
    {
        $resp = $this->getJson(route('admin.kiosks.statistics'));
        // admin routes redirect to admin.login when unauthenticated (302) or 401 for JSON
        $this->assertTrue(in_array($resp->status(), [401,302,404]), 'Expected auth failure, got '.$resp->status());
    }

    // ==================== View Professionalism ====================
    public function test_kiosk_index_uses_professional_layout()
    {
        $this->actingAs($this->admin, 'admin');
        Kiosk::factory()->create();
        $resp = $this->get(route('admin.kiosks.index'));
        $resp->assertOk();
        // professional: gradient header, Inter, 14px cards, no small-box baggage
        $resp->assertSee('linear-gradient(135deg,#1e293b', false);
        $resp->assertDontSee('small-box bg-info', false);
        $resp->assertDontSee('data-toggle="modal"', false);
        $resp->assertSee('data-bs-toggle="dropdown"', false);
    }

    public function test_kiosk_create_uses_admin_route_not_kiosks_index()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->get(route('admin.kiosks.create'));
        $resp->assertOk();
        $resp->assertSee(route('admin.kiosks.store'), false);
        // old view incorrectly used route('kiosks.index') without admin prefix — should not be present
        $resp->assertDontSee('kiosks.index', false);
        $content = $resp->getContent();
        $this->assertStringNotContainsString("route('kiosks.", $content);
    }
}
