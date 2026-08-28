<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Permission;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SubUsersTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected Doctor $doctor;
    protected Specialty $specialty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specialty = Specialty::factory()->create();

        $this->doctorUser = User::factory()->create([
            'role' => 'doctor',
            'email_verified_at' => now(),
        ]);

        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => true,
        ]);

        // Seed permissions
        $this->seedPermissions();
    }

    private function seedPermissions(): void
    {
        // Non-restricted
        Permission::create([
            'name' => 'appointments',
            'display_name' => 'Appointments',
            'route_pattern' => 'doctor.appointments.*',
            'category' => 'appointments',
            'is_restricted' => false,
            'sort_order' => 10,
        ]);
        Permission::create([
            'name' => 'reviews',
            'display_name' => 'Reviews',
            'route_pattern' => 'doctor.reviews.*',
            'category' => 'communication',
            'is_restricted' => false,
            'sort_order' => 20,
        ]);
        // Restricted - should NOT be assignable to sub-users
        Permission::create([
            'name' => 'sub_users',
            'display_name' => 'Sub-User Management',
            'route_pattern' => 'sub-users.*',
            'category' => 'restricted',
            'is_restricted' => true,
            'sort_order' => 104,
        ]);
        Permission::create([
            'name' => 'diagnosis',
            'display_name' => 'Diagnoses',
            'route_pattern' => 'diagnosis.*',
            'category' => 'restricted',
            'is_restricted' => true,
            'sort_order' => 103,
        ]);
    }

    private function createSubUser(User $parent, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'doctor',
            'is_sub_user' => true,
            'parent_user_id' => $parent->id,
            'sub_user_role' => 'assistant',
        ], $overrides));
    }

    // === AUTH & MIDDLEWARE ===

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/sub-users')->assertRedirect(route('login'));
        $this->get('/sub-users/create')->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_sub_users(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($patient);

        $this->get('/sub-users')->assertStatus(403);
        $this->get('/sub-users/create')->assertStatus(403);
        $this->post('/sub-users', [])->assertStatus(403);
    }

    public function test_sub_user_cannot_manage_sub_users(): void
    {
        $subUser = $this->createSubUser($this->doctorUser);
        // Need doctor profile for parent already exists, sub-user inherits but middleware checks isSubUser
        $this->actingAs($subUser);

        $this->get('/sub-users')->assertStatus(403);
        $this->get('/sub-users/create')->assertStatus(403);
    }

    public function test_doctor_without_active_profile_is_forbidden(): void
    {
        $inactiveDoctorUser = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create([
            'user_id' => $inactiveDoctorUser->id,
            'specialty_id' => $this->specialty->id,
            'is_active' => false,
        ]);

        $this->actingAs($inactiveDoctorUser);
        $this->get('/sub-users')->assertStatus(403);
    }

    public function test_doctor_can_view_index_empty_state(): void
    {
        $this->actingAs($this->doctorUser);

        $response = $this->get('/sub-users');
        $response->assertStatus(200)
            ->assertViewIs('sub-users.index')
            ->assertSee('No Sub-Users Yet')
            ->assertSee('Add Your First Sub-User');
    }

    public function test_index_shows_only_own_sub_users(): void
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create(['user_id' => $otherDoctor->id, 'specialty_id' => $this->specialty->id, 'is_active' => true]);

        $ownSub = $this->createSubUser($this->doctorUser, ['name' => 'Own Assistant']);
        $otherSub = $this->createSubUser($otherDoctor, ['name' => 'Other Assistant']);

        $this->actingAs($this->doctorUser);
        $response = $this->get('/sub-users');
        $response->assertStatus(200);
        $response->assertSee('Own Assistant');
        $response->assertDontSee('Other Assistant');
    }

    public function test_index_displays_stats(): void
    {
        $this->createSubUser($this->doctorUser, ['sub_user_role' => 'secretary']);
        $this->createSubUser($this->doctorUser, ['sub_user_role' => 'nurse']);

        $this->actingAs($this->doctorUser);
        $response = $this->get('/sub-users');
        $response->assertSee('Total Team');
        $response->assertSee('Active');
    }

    // === CREATE FORM ===

    public function test_create_shows_available_permissions_grouped(): void
    {
        $this->actingAs($this->doctorUser);
        $response = $this->get('/sub-users/create');
        $response->assertStatus(200)->assertViewIs('sub-users.create');
        // Only non-restricted should be visible
        $response->assertSee('Appointments');
        $response->assertDontSee('Sub-User Management'); // restricted
    }

    // === STORE ===

    public function test_store_creates_sub_user_with_hashed_password(): void
    {
        $this->actingAs($this->doctorUser);

        $perm = Permission::where('name', 'appointments')->first();

        $response = $this->post('/sub-users', [
            'name' => 'Nurse Joy',
            'email' => 'nurse@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'sub_user_role' => 'nurse',
            'phone' => '1234567890',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('sub-users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'nurse@example.com',
            'is_sub_user' => true,
            'parent_user_id' => $this->doctorUser->id,
            'sub_user_role' => 'nurse',
            'role' => 'doctor',
        ]);

        $user = User::where('email', 'nurse@example.com')->first();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertTrue($user->permissions()->where('name', 'appointments')->exists());
        $this->assertEquals($this->doctorUser->id, $user->permissions()->first()->pivot->granted_by);
    }

    public function test_store_filters_restricted_permissions(): void
    {
        $this->actingAs($this->doctorUser);

        $restricted = Permission::where('name', 'sub_users')->first();
        $allowed = Permission::where('name', 'appointments')->first();

        $this->post('/sub-users', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'sub_user_role' => 'assistant',
            'permissions' => [$allowed->id, $restricted->id],
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->permissions()->where('name', 'appointments')->exists());
        $this->assertFalse($user->permissions()->where('name', 'sub_users')->exists(), 'Restricted permission should be filtered');
        $this->assertEquals(1, $user->permissions()->count());
    }

    public function test_store_validation_requires_name(): void
    {
        $this->actingAs($this->doctorUser);
        $response = $this->post('/sub-users', [
            'email' => 'a@b.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'sub_user_role' => 'assistant',
        ]);
        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validation_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);
        $this->actingAs($this->doctorUser);

        $response = $this->post('/sub-users', [
            'name' => 'X',
            'email' => 'dup@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'sub_user_role' => 'assistant',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_store_validation_rejects_invalid_permission(): void
    {
        $this->actingAs($this->doctorUser);
        $response = $this->post('/sub-users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'sub_user_role' => 'assistant',
            'permissions' => [9999],
        ]);
        $response->assertSessionHasErrors(['permissions.0']);
    }

    public function test_store_validation_requires_password_confirmation(): void
    {
        $this->actingAs($this->doctorUser);
        $response = $this->post('/sub-users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Mismatch',
            'sub_user_role' => 'assistant',
        ]);
        $response->assertSessionHasErrors(['password']);
    }

    // === SHOW ===

    public function test_show_own_sub_user_success(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $this->actingAs($this->doctorUser);

        $response = $this->get("/sub-users/{$sub->id}");
        $response->assertStatus(200)->assertViewIs('sub-users.show')->assertSee($sub->name);
    }

    public function test_show_other_doctors_sub_user_404(): void
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create(['user_id' => $otherDoctor->id, 'specialty_id' => $this->specialty->id, 'is_active' => true]);
        $otherSub = $this->createSubUser($otherDoctor);

        $this->actingAs($this->doctorUser);
        $this->get("/sub-users/{$otherSub->id}")->assertStatus(404);
    }

    public function test_show_non_sub_user_404(): void
    {
        $normal = User::factory()->create(['role' => 'patient']);
        $this->actingAs($this->doctorUser);
        $this->get("/sub-users/{$normal->id}")->assertStatus(404);
    }

    // === EDIT ===

    public function test_edit_shows_form_with_permissions(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $this->actingAs($this->doctorUser);

        $response = $this->get("/sub-users/{$sub->id}/edit");
        $response->assertStatus(200)->assertViewIs('sub-users.edit')
            ->assertSee($sub->email);
    }

    public function test_edit_other_parent_forbidden(): void
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create(['user_id' => $otherDoctor->id, 'specialty_id' => $this->specialty->id, 'is_active' => true]);
        $otherSub = $this->createSubUser($otherDoctor);

        $this->actingAs($this->doctorUser);
        $this->get("/sub-users/{$otherSub->id}/edit")->assertStatus(404);
    }

    // === UPDATE ===

    public function test_update_changes_name_and_role_and_permissions(): void
    {
        $sub = $this->createSubUser($this->doctorUser, ['name' => 'Old']);
        $oldPerm = Permission::where('name', 'appointments')->first();
        $newPerm = Permission::where('name', 'reviews')->first();
        $sub->grantPermission($oldPerm, $this->doctorUser);

        $this->actingAs($this->doctorUser);
        $response = $this->put("/sub-users/{$sub->id}", [
            'name' => 'New Name',
            'email' => $sub->email,
            'sub_user_role' => 'secretary',
            'phone' => '999',
            'permissions' => [$newPerm->id],
        ]);

        $response->assertRedirect(route('sub-users.index'));
        $sub->refresh();
        $this->assertEquals('New Name', $sub->name);
        $this->assertEquals('secretary', $sub->sub_user_role);
        $this->assertFalse($sub->permissions()->where('name', 'appointments')->exists());
        $this->assertTrue($sub->permissions()->where('name', 'reviews')->exists());
    }

    public function test_update_email_must_remain_unique(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->doctorUser);
        $response = $this->put("/sub-users/{$sub->id}", [
            'name' => 'X',
            'email' => 'taken@example.com',
            'sub_user_role' => 'assistant',
        ]);
        $response->assertSessionHasErrors(['email']);
    }

    public function test_update_keeps_email_if_unchanged(): void
    {
        $sub = $this->createSubUser($this->doctorUser, ['email' => 'keep@example.com']);
        $this->actingAs($this->doctorUser);

        $response = $this->put("/sub-users/{$sub->id}", [
            'name' => 'Keep',
            'email' => 'keep@example.com',
            'sub_user_role' => 'assistant',
        ]);
        $response->assertRedirect(route('sub-users.index'));
        $this->assertDatabaseHas('users', ['id' => $sub->id, 'email' => 'keep@example.com']);
    }

    public function test_update_with_password_changes_hash(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $oldHash = $sub->password;

        $this->actingAs($this->doctorUser);
        $response = $this->put("/sub-users/{$sub->id}", [
            'name' => $sub->name,
            'email' => $sub->email,
            'sub_user_role' => 'assistant',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        $response->assertRedirect(route('sub-users.index'));
        $sub->refresh();
        $this->assertNotEquals($oldHash, $sub->password);
        $this->assertTrue(Hash::check('NewPassword123!', $sub->password));
    }

    public function test_update_detaches_permissions_when_none_sent(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $perm = Permission::where('name', 'appointments')->first();
        $sub->grantPermission($perm, $this->doctorUser);
        $this->assertEquals(1, $sub->permissions()->count());

        $this->actingAs($this->doctorUser);
        $this->put("/sub-users/{$sub->id}", [
            'name' => 'X',
            'email' => $sub->email,
            'sub_user_role' => 'assistant',
            // no permissions key
        ]);

        $sub->refresh();
        $this->assertEquals(0, $sub->permissions()->count());
    }

    public function test_update_cannot_assign_restricted(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $restricted = Permission::where('name', 'sub_users')->first();

        $this->actingAs($this->doctorUser);
        $this->put("/sub-users/{$sub->id}", [
            'name' => 'X',
            'email' => $sub->email,
            'sub_user_role' => 'assistant',
            'permissions' => [$restricted->id],
        ]);

        $sub->refresh();
        $this->assertFalse($sub->permissions()->where('name', 'sub_users')->exists());
    }

    // === DESTROY ===

    public function test_destroy_deletes_own_sub_user(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $this->actingAs($this->doctorUser);

        $response = $this->delete("/sub-users/{$sub->id}");
        $response->assertRedirect(route('sub-users.index'));
        $this->assertDatabaseMissing('users', ['id' => $sub->id]);
    }

    public function test_destroy_other_parent_returns_404(): void
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create(['user_id' => $otherDoctor->id, 'specialty_id' => $this->specialty->id, 'is_active' => true]);
        $otherSub = $this->createSubUser($otherDoctor);

        $this->actingAs($this->doctorUser);
        $this->delete("/sub-users/{$otherSub->id}")->assertStatus(404);
        $this->assertDatabaseHas('users', ['id' => $otherSub->id]);
    }

    public function test_destroy_cascades_user_permissions(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $perm = Permission::where('name', 'appointments')->first();
        $sub->grantPermission($perm, $this->doctorUser);
        $this->assertDatabaseHas('user_permissions', ['user_id' => $sub->id]);

        $this->actingAs($this->doctorUser);
        $this->delete("/sub-users/{$sub->id}");

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $sub->id]);
    }

    // === TOGGLE STATUS (CURRENTLY STUB) ===

    public function test_toggle_status_returns_success_but_does_not_change_state(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $this->actingAs($this->doctorUser);

        $response = $this->patch("/sub-users/{$sub->id}/toggle-status");
        $response->assertRedirect(route('sub-users.index'))
            ->assertSessionHas('success');

        // Currently controller does NOT modify DB — document bug
        $sub->refresh();
        $this->assertTrue($sub->is_sub_user); // still sub-user, no active flag exists
        // TODO: If is_active column is added, this test will need to assert toggle
    }

    public function test_toggle_status_other_parent_404(): void
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        Doctor::factory()->create(['user_id' => $otherDoctor->id, 'specialty_id' => $this->specialty->id, 'is_active' => true]);
        $otherSub = $this->createSubUser($otherDoctor);

        $this->actingAs($this->doctorUser);
        $this->patch("/sub-users/{$otherSub->id}/toggle-status")->assertStatus(404);
    }

    // === EDGE: Direct model checks ===

    public function test_sub_user_inherits_doctor_via_parent(): void
    {
        $sub = $this->createSubUser($this->doctorUser);
        $this->assertEquals($this->doctor->id, $sub->getEffectiveDoctor()->id);
        $this->assertEquals($this->doctorUser->id, $sub->getEffectiveDoctorUser()->id);
    }

    public function test_can_access_patient_via_parent_doctor(): void
    {
        $patient = User::factory()->create(['role' => 'patient', 'primary_doctor_id' => $this->doctorUser->id]);
        $sub = $this->createSubUser($this->doctorUser);

        $this->assertTrue($sub->canAccessPatient($patient));
    }
}
